<?php

namespace App\Services\Agent;

use App\Models\AgentMessage;
use App\Models\AgentTask;
use App\Models\Customer;
use App\Models\Opportunity;
use Carbon\Carbon;

/**
 * Executes the `create_opportunity` Claude tool. Highest-risk tool in the v1 library
 * (per fm-agent-context.md's rollout order) — it's the only one that creates new records
 * rather than acting on an existing opportunity, so duplicate detection matters most here.
 *
 * OpportunityController::store() (app/Http/Controllers/Pages/OpportunityController.php)
 * always requires an *existing* parent_customer_id — it never creates a Customer itself.
 * This service does both steps: resolve-or-create the Customer(s), then create the
 * Opportunity.
 */
class CreateOpportunityService
{
    /** Duplicate-check threshold — deliberately lower than find_opportunity's 0.85
     *  auto-resolve score, since this is a warning gate, not an auto-resolve. */
    private const DUPLICATE_SCORE_THRESHOLD = 0.6;

    private const DUPLICATE_WINDOW_DAYS = 60;

    private const INCOMPLETE_INTAKE_FIELDS = ['address', 'claim_number', 'insurance_company'];

    /** Fuzzy-match closeness threshold for resolveExistingParent()'s third pass —
     *  only reached when neither exact nor prefix matching found anything. 0.78 comfortably
     *  catches an inserted-word variant like "First Onsite Property Restoration" vs the
     *  on-file "First OnSite Restoration" (~84%) while staying well above where bare
     *  first-name-vs-full-name collisions land (~50-67%, e.g. "Eric" vs "Eric Shin"). */
    private const PARENT_FUZZY_THRESHOLD = 0.78;

    /** Required lead over the runner-up for the fuzzy pass to auto-pick rather than
     *  throw as ambiguous — same "don't guess between two close candidates" principle
     *  as find_opportunity's AUTO_RESOLVE_MARGIN, just tighter since this is a single
     *  company match rather than a ranked list. */
    private const PARENT_FUZZY_MARGIN = 0.08;

    /** Agent-created opportunities default to Marco Bruni (employee #2) as the sales
     *  person — confirmed with Richard (2026-08-18), agent-created only, not app-wide.
     *  sales_person_1 has no exists:employees,id validation anywhere (see
     *  UpdateOpportunityService's docblock for the same note) — it's a plain string in
     *  practice populated with a numeric employee id; '2' matches that existing
     *  convention rather than a name, so it looks identical to a human-entered value. */
    private const DEFAULT_SALES_PERSON = '2';

    public function __construct(private readonly FindOpportunityService $findOpportunity)
    {
    }

    /**
     * @return array{opportunity_id: int, customer_id: int, parent_customer_id: int,
     *     incomplete_intake_fields: string[]}
     */
    public function execute(
        AgentTask $task,
        string $clientName,
        ?string $parentCustomerName,
        ?string $address,
        ?string $claimNumber,
        ?string $insuranceCompany,
        ?string $adjuster,
        ?string $policyNumber,
        ?string $dol,
        ?bool $requiresRfm,
        ?string $jobNo,
    ): array {
        if ($task->opportunity_id !== null) {
            throw new AgentToolValidationException(
                'An opportunity is already resolved for this task — use update_opportunity or the attach tools instead of creating a new one.'
            );
        }

        $clientName = trim($clientName);
        if ($clientName === '') {
            throw new AgentToolValidationException('client_name is required.');
        }

        $address = trim((string) $address) ?: null;
        $claimNumber = trim((string) $claimNumber) ?: null;
        $insuranceCompany = trim((string) $insuranceCompany) ?: null;
        $adjuster = trim((string) $adjuster) ?: null;
        $policyNumber = trim((string) $policyNumber) ?: null;
        $dol = trim((string) $dol) ?: null;
        $parentCustomerName = trim((string) $parentCustomerName) ?: null;
        $jobNo = trim((string) $jobNo) ?: null;

        if ($dol !== null) {
            try {
                $dol = Carbon::parse($dol)->toDateString();
            } catch (\Exception) {
                throw new AgentToolValidationException("dol (\"{$dol}\") is not a recognizable date.");
            }
        }

        $this->assertNoLikelyDuplicate($clientName, $address, $claimNumber, $jobNo);

        $parentId = $parentCustomerName !== null
            ? $this->resolveExistingParent($task, $parentCustomerName)
            : null;

        $jobSiteCustomer = Customer::create([
            'parent_id' => $parentId,
            'name' => $clientName,
            'address' => $address,
            'insurance_company' => $insuranceCompany,
            'adjuster' => $adjuster,
            'policy_number' => $policyNumber,
            'claim_number' => $claimNumber,
            'dol' => $dol,
            'created_by' => $task->requester_user_id,
            'updated_by' => $task->requester_user_id,
        ]);

        $resolvedParentId = $parentId ?? $jobSiteCustomer->id;

        $opportunity = Opportunity::create([
            'parent_customer_id' => $resolvedParentId,
            'job_site_customer_id' => $jobSiteCustomer->id,
            'job_no' => $jobNo,
            'status' => 'New',
            'requires_rfm' => $requiresRfm ?? true,
            'sales_person_1' => self::DEFAULT_SALES_PERSON,
            'created_by' => $task->requester_user_id,
            'updated_by' => $task->requester_user_id,
            'initiated_by' => $task->requester_user_id,
        ]);

        $task->opportunity_id = $opportunity->id;
        $task->save();

        $incompleteFields = $this->incompleteIntakeFields($address, $claimNumber, $insuranceCompany);

        $this->logCreation($task, $opportunity, $jobSiteCustomer, $parentId, $incompleteFields);

        return [
            'opportunity_id' => $opportunity->id,
            'customer_id' => $jobSiteCustomer->id,
            'parent_customer_id' => $resolvedParentId,
            'incomplete_intake_fields' => $incompleteFields,
        ];
    }

    /**
     * Reuses FindOpportunityService's scoring (searchCandidates()) rather than
     * reimplementing tokenized-LIKE-plus-similar_text() matching a second time. Any
     * candidate scoring at or above the (lower, warning-only) duplicate threshold whose
     * opportunity was created within the recent window blocks creation entirely — no
     * override path, per the "never silently duplicate" design principle.
     */
    private function assertNoLikelyDuplicate(string $clientName, ?string $address, ?string $claimNumber, ?string $jobNo): void
    {
        $candidates = $this->findOpportunity->searchCandidates($clientName, $address, $claimNumber, $jobNo);
        $cutoff = Carbon::now()->subDays(self::DUPLICATE_WINDOW_DAYS);

        $recentMatches = array_filter($candidates, function (array $c) use ($cutoff) {
            if ($c['score'] < self::DUPLICATE_SCORE_THRESHOLD || $c['created_at'] === null) {
                return false;
            }

            return Carbon::parse($c['created_at'])->greaterThanOrEqualTo($cutoff);
        });

        if (empty($recentMatches)) {
            return;
        }

        $list = collect($recentMatches)
            ->map(fn (array $c) => "opportunity #{$c['opportunity_id']} (job_no={$c['job_no']}, created {$c['created_at']}, score={$c['score']})")
            ->implode('; ');

        throw new AgentToolValidationException(
            "Possible duplicate — this looks like it may already exist: {$list}. Not creating a new opportunity; confirm with a human first."
        );
    }

    /**
     * Exact (case-insensitive) name match first, then a whitespace-insensitive "prefix"
     * match — e.g. "First Onsite", "FirstOnSite", and "First OnSite Restoration" all
     * normalize to the same space-stripped prefix relationship — and finally, only if
     * both of those found nothing, a similar_text() closeness pass (same function/style
     * as FindOpportunityService::similarity()) that tolerates an inserted/extra word
     * (e.g. referral partner signs as "First Onsite Property Restoration" when the
     * on-file record is "First OnSite Restoration" — not a prefix relationship, but
     * ~84% similar). Still not arbitrary fuzzy-guessing for a company-level record: the
     * fuzzy pass only fires as a last resort, requires crossing PARENT_FUZZY_THRESHOLD,
     * and still throws rather than guesses when the top two candidates are close
     * (PARENT_FUZZY_MARGIN) — a match is either unambiguous or it isn't. Never creates a
     * new parent from an unmatched name, to avoid spawning duplicate company records
     * from a misspelled/misremembered name.
     */
    private function resolveExistingParent(AgentTask $task, string $parentCustomerName): int
    {
        $needle = mb_strtolower($parentCustomerName);
        $needleCompact = str_replace(' ', '', $needle);

        $exact = Customer::whereNull('parent_id')
            ->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(name) = ?', [$needle])
                    ->orWhereRaw('LOWER(company_name) = ?', [$needle]);
            })
            ->get(['id']);

        if ($exact->count() === 1) {
            return $exact->first()->id;
        }
        if ($exact->count() > 1) {
            throw new AgentToolValidationException(
                "Multiple existing customers named \"{$parentCustomerName}\" found — cannot resolve unambiguously."
            );
        }

        $prefix = Customer::whereNull('parent_id')
            ->where(function ($q) use ($needleCompact) {
                // Either direction, spaces ignored on both sides: the input is a
                // shortened/differently-spaced form of the name on file
                // ("FirstOnSite" -> "First OnSite Restoration"), or vice versa.
                $q->whereRaw('REPLACE(LOWER(name), \' \', \'\') LIKE ?', [$needleCompact . '%'])
                    ->orWhereRaw('REPLACE(LOWER(company_name), \' \', \'\') LIKE ?', [$needleCompact . '%'])
                    ->orWhereRaw('? LIKE CONCAT(REPLACE(LOWER(name), \' \', \'\'), \'%\')', [$needleCompact])
                    ->orWhereRaw('? LIKE CONCAT(REPLACE(LOWER(company_name), \' \', \'\'), \'%\')', [$needleCompact]);
            })
            ->get(['id']);

        if ($prefix->count() === 1) {
            return $prefix->first()->id;
        }
        if ($prefix->count() > 1) {
            throw new AgentToolValidationException(
                "Multiple existing customers named \"{$parentCustomerName}\" found — cannot resolve unambiguously."
            );
        }

        $fuzzyId = $this->resolveExistingParentByFuzzyMatch($task, $parentCustomerName);
        if ($fuzzyId !== null) {
            return $fuzzyId;
        }

        throw new AgentToolValidationException(
            "No existing customer named \"{$parentCustomerName}\" found. Omit parent_customer_name to create a new standalone customer instead."
        );
    }

    /**
     * Last-resort closeness pass — every standalone (parent_id IS NULL) customer is
     * scored against the needle via similar_text() on both `name` and `company_name`,
     * keeping the best of the two per candidate. Logged to agent_messages regardless of
     * outcome (matched, ambiguous, or no candidate crossed the threshold) per the spec's
     * "every fuzzy-matching decision is logged with a confidence score" principle.
     */
    private function resolveExistingParentByFuzzyMatch(AgentTask $task, string $parentCustomerName): ?int
    {
        $needle = mb_strtolower(trim($parentCustomerName));

        $scored = Customer::whereNull('parent_id')
            ->get(['id', 'name', 'company_name'])
            ->map(function (Customer $c) use ($needle) {
                return [
                    'id' => $c->id,
                    'label' => $c->company_name ?: $c->name,
                    'score' => max(
                        $this->similarity($needle, (string) $c->name),
                        $this->similarity($needle, (string) $c->company_name),
                    ),
                ];
            })
            ->filter(fn (array $c) => $c['score'] >= self::PARENT_FUZZY_THRESHOLD)
            ->sortByDesc('score')
            ->values();

        if ($scored->isEmpty()) {
            return null;
        }

        if ($scored->count() > 1 && ($scored[0]['score'] - $scored[1]['score']) < self::PARENT_FUZZY_MARGIN) {
            $this->logFuzzyParentMatch($task, $parentCustomerName, null, $scored->take(3)->all());

            throw new AgentToolValidationException(
                "\"{$parentCustomerName}\" is ambiguous against existing customers ("
                . $scored->take(3)->map(fn (array $c) => "{$c['label']} (" . round($c['score'] * 100) . '%)')->implode(', ')
                . ') — cannot resolve unambiguously.'
            );
        }

        $best = $scored->first();
        $this->logFuzzyParentMatch($task, $parentCustomerName, $best, $scored->take(3)->all());

        return $best['id'];
    }

    private function similarity(string $needle, string $candidate): float
    {
        $candidate = mb_strtolower(trim($candidate));
        if ($needle === '' || $candidate === '') {
            return 0.0;
        }

        similar_text($needle, $candidate, $percent);

        return $percent / 100;
    }

    /** @param array<int, array{id: int, label: string, score: float}> $topCandidates */
    private function logFuzzyParentMatch(AgentTask $task, string $requestedName, ?array $matched, array $topCandidates): void
    {
        $candidateList = collect($topCandidates)
            ->map(fn (array $c) => "{$c['label']} (" . round($c['score'] * 100) . '%)')
            ->implode(', ');

        $outcome = $matched !== null
            ? "matched to \"{$matched['label']}\" (customer #{$matched['id']}, " . round($matched['score'] * 100) . '% similar)'
            : 'no unambiguous match';

        AgentMessage::create([
            'task_id' => $task->id,
            'sender' => 'agent',
            'body' => "create_opportunity parent-customer fuzzy match for \"{$requestedName}\": {$outcome}. Candidates considered: {$candidateList}.",
        ]);
    }

    /** @return string[] */
    private function incompleteIntakeFields(?string $address, ?string $claimNumber, ?string $insuranceCompany): array
    {
        $values = ['address' => $address, 'claim_number' => $claimNumber, 'insurance_company' => $insuranceCompany];

        return array_values(array_filter(
            self::INCOMPLETE_INTAKE_FIELDS,
            fn (string $field) => empty($values[$field]),
        ));
    }

    private function logCreation(
        AgentTask $task,
        Opportunity $opportunity,
        Customer $jobSiteCustomer,
        ?int $existingParentId,
        array $incompleteFields,
    ): void {
        $parentNote = $existingParentId !== null
            ? "linked to existing parent customer #{$existingParentId}"
            : 'created as a new standalone customer (parent = job site)';

        $intakeNote = empty($incompleteFields)
            ? 'intake complete'
            : 'incomplete intake — missing: ' . implode(', ', $incompleteFields);

        AgentMessage::create([
            'task_id' => $task->id,
            'sender' => 'agent',
            'body' => "create_opportunity: created opportunity #{$opportunity->id} for customer #{$jobSiteCustomer->id} ({$parentNote}); {$intakeNote}.",
        ]);
    }
}
