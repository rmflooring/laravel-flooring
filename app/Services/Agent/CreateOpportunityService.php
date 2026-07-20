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

        if ($dol !== null) {
            try {
                $dol = Carbon::parse($dol)->toDateString();
            } catch (\Exception) {
                throw new AgentToolValidationException("dol (\"{$dol}\") is not a recognizable date.");
            }
        }

        $this->assertNoLikelyDuplicate($clientName, $address, $claimNumber);

        $parentId = $parentCustomerName !== null
            ? $this->resolveExistingParent($parentCustomerName)
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
            'status' => 'New',
            'requires_rfm' => $requiresRfm ?? true,
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
    private function assertNoLikelyDuplicate(string $clientName, ?string $address, ?string $claimNumber): void
    {
        $candidates = $this->findOpportunity->searchCandidates($clientName, $address, $claimNumber);
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
     * Exact (case-insensitive) name match only against existing standalone customers —
     * same "never fuzzy-guess for a company-level record" invariant as
     * UpdateOpportunityService::resolveProjectManagerId(). Never creates a new parent
     * from an unmatched name, to avoid spawning duplicate company records from a
     * misspelled/misremembered name.
     */
    private function resolveExistingParent(string $parentCustomerName): int
    {
        $matches = Customer::whereNull('parent_id')
            ->where(function ($q) use ($parentCustomerName) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($parentCustomerName)])
                    ->orWhereRaw('LOWER(company_name) = ?', [mb_strtolower($parentCustomerName)]);
            })
            ->get(['id']);

        if ($matches->count() === 1) {
            return $matches->first()->id;
        }

        if ($matches->count() > 1) {
            throw new AgentToolValidationException(
                "Multiple existing customers named \"{$parentCustomerName}\" found — cannot resolve unambiguously."
            );
        }

        throw new AgentToolValidationException(
            "No existing customer named \"{$parentCustomerName}\" found. Omit parent_customer_name to create a new standalone customer instead."
        );
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
