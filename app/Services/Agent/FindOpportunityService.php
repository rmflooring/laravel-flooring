<?php

namespace App\Services\Agent;

use App\Models\AgentMessage;
use App\Models\AgentTask;
use App\Models\Opportunity;
use Illuminate\Support\Collection;

/**
 * Executes the `find_opportunity` Claude tool: fuzzy-matches a client name / address /
 * claim number mentioned in an email against existing opportunities, via their linked
 * job-site customer (where address/claim_number actually live) and parent customer
 * (either could be the name mentioned). Replaces the Module 1 job-number-regex stand-in
 * in ProcessAgentTask::resolveOpportunity() for emails that don't mention a job number.
 *
 * No fuzzy-matching library exists in this codebase — scoring uses PHP's built-in
 * similar_text() rather than adding a dependency for a single feature.
 */
class FindOpportunityService
{
    private const MIN_CANDIDATE_SCORE = 0.35;

    private const AUTO_RESOLVE_SCORE = 0.85;

    private const AUTO_RESOLVE_MARGIN = 0.2;

    private const MAX_CANDIDATES = 5;

    private const CLAIM_WEIGHT = 0.5;

    private const NAME_WEIGHT = 0.3;

    private const ADDRESS_WEIGHT = 0.2;

    /**
     * @return array{resolved: bool, opportunity_id: ?int, candidates: array<int, array{
     *     opportunity_id: int, job_no: ?string, customer_name: ?string, address: ?string,
     *     claim_number: ?string, created_at: ?string, score: float
     * }>}
     */
    public function execute(
        AgentTask $task,
        ?string $clientName,
        ?string $address,
        ?string $claimNumber,
    ): array {
        [$clientName, $address, $claimNumber] = $this->normalizeCriteria($clientName, $address, $claimNumber);

        if ($clientName === null && $address === null && $claimNumber === null) {
            throw new AgentToolValidationException(
                'At least one of client_name, address, or claim_number is required.'
            );
        }

        $candidates = $this->searchCandidates($clientName, $address, $claimNumber);

        $resolvedId = $this->maybeAutoResolve($candidates);
        if ($resolvedId !== null) {
            $task->opportunity_id = $resolvedId;
            $task->save();
        }

        $this->logSearch($task, $clientName, $address, $claimNumber, $candidates, $resolvedId);

        return [
            'resolved' => $resolvedId !== null,
            'opportunity_id' => $resolvedId,
            'candidates' => $candidates,
        ];
    }

    /** @return array{0: ?string, 1: ?string, 2: ?string} */
    private function normalizeCriteria(?string $clientName, ?string $address, ?string $claimNumber): array
    {
        return [
            trim((string) $clientName) ?: null,
            trim((string) $address) ?: null,
            trim((string) $claimNumber) ?: null,
        ];
    }

    /**
     * Scored, filtered (≥ MIN_CANDIDATE_SCORE), sorted-desc, capped-at-MAX_CANDIDATES
     * candidate list — shared between find_opportunity's own resolution and
     * CreateOpportunityService's duplicate check. Callers are expected to have already
     * trimmed/nulled their inputs (see normalizeCriteria()).
     *
     * @return array<int, array{opportunity_id: int, job_no: ?string, customer_name: ?string,
     *     address: ?string, claim_number: ?string, created_at: ?string, score: float}>
     */
    public function searchCandidates(?string $clientName, ?string $address, ?string $claimNumber): array
    {
        $candidates = $this->scoreCandidates(
            $this->gatherCandidates($clientName, $address, $claimNumber),
            $clientName,
            $address,
            $claimNumber,
        );

        $candidates = array_values(array_filter(
            $candidates,
            fn (array $c) => $c['score'] >= self::MIN_CANDIDATE_SCORE,
        ));

        usort($candidates, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($candidates, 0, self::MAX_CANDIDATES);
    }

    /**
     * Broad SQL LIKE pre-filter (tokenized) to keep the candidate set small before
     * scoring in PHP — same tokenized-LIKE-over-relations style as
     * OpportunityController::applyOpportunityFilters, extended to token-split input.
     */
    private function gatherCandidates(?string $clientName, ?string $address, ?string $claimNumber): Collection
    {
        $nameTokens = $this->tokenize($clientName);
        $addressTokens = $this->tokenize($address);

        $query = Opportunity::query()->with(['parentCustomer', 'jobSiteCustomer']);

        $query->where(function ($outer) use ($nameTokens, $addressTokens, $claimNumber) {
            $any = false;

            foreach ($nameTokens as $token) {
                $any = true;
                $outer->orWhereHas('parentCustomer', function ($c) use ($token) {
                    $c->where('name', 'like', "%{$token}%")->orWhere('company_name', 'like', "%{$token}%");
                })->orWhereHas('jobSiteCustomer', function ($c) use ($token) {
                    $c->where('name', 'like', "%{$token}%")->orWhere('company_name', 'like', "%{$token}%");
                });
            }

            foreach ($addressTokens as $token) {
                $any = true;
                $outer->orWhereHas('jobSiteCustomer', function ($c) use ($token) {
                    $c->where('address', 'like', "%{$token}%")->orWhere('city', 'like', "%{$token}%");
                });
            }

            if ($claimNumber !== null) {
                $any = true;
                $outer->orWhereHas('jobSiteCustomer', function ($c) use ($claimNumber) {
                    $c->where('claim_number', 'like', "%{$claimNumber}%");
                });
            }

            // No usable tokens at all (e.g. only very short words) — fall back to
            // matching nothing rather than the whole table.
            if (! $any) {
                $outer->whereRaw('1 = 0');
            }
        });

        return $query->limit(200)->get();
    }

    /** @return string[] */
    private function tokenize(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        return array_values(array_unique(array_filter(
            preg_split('/[\s,]+/', $value) ?: [],
            fn (string $t) => mb_strlen($t) >= 3,
        )));
    }

    /**
     * @param  Collection<int, Opportunity>  $opportunities
     * @return array<int, array{opportunity_id: int, job_no: ?string, customer_name: ?string,
     *     address: ?string, claim_number: ?string, created_at: ?string, score: float}>
     */
    private function scoreCandidates(
        Collection $opportunities,
        ?string $clientName,
        ?string $address,
        ?string $claimNumber,
    ): array {
        $providedWeight = ($claimNumber !== null ? self::CLAIM_WEIGHT : 0)
            + ($clientName !== null ? self::NAME_WEIGHT : 0)
            + ($address !== null ? self::ADDRESS_WEIGHT : 0);

        if ($providedWeight <= 0.0) {
            return [];
        }

        $results = [];

        foreach ($opportunities as $opportunity) {
            $jobSite = $opportunity->jobSiteCustomer;
            $parent = $opportunity->parentCustomer;

            $score = 0.0;

            if ($claimNumber !== null) {
                $existing = trim((string) ($jobSite?->claim_number ?? ''));
                $claimScore = ($existing !== '' && strcasecmp($existing, $claimNumber) === 0) ? 1.0 : 0.0;
                $score += $claimScore * self::CLAIM_WEIGHT;
            }

            if ($clientName !== null) {
                $nameScore = max(
                    $this->similarity($clientName, $jobSite?->name ?? ''),
                    $this->similarity($clientName, $jobSite?->company_name ?? ''),
                    $this->similarity($clientName, $parent?->name ?? ''),
                    $this->similarity($clientName, $parent?->company_name ?? ''),
                );
                $score += $nameScore * self::NAME_WEIGHT;
            }

            if ($address !== null) {
                $candidateAddress = trim(($jobSite?->address ?? '') . ' ' . ($jobSite?->city ?? ''));
                $score += $this->similarity($address, $candidateAddress) * self::ADDRESS_WEIGHT;
            }

            $results[] = [
                'opportunity_id' => $opportunity->id,
                'job_no' => $opportunity->job_no,
                'customer_name' => $jobSite?->name ?? $parent?->name ?? null,
                'address' => $jobSite?->address ?? null,
                'claim_number' => $jobSite?->claim_number ?? null,
                'created_at' => $opportunity->created_at?->toDateTimeString(),
                'score' => round($score / $providedWeight, 4),
            ];
        }

        return $results;
    }

    private function similarity(string $a, string $b): float
    {
        $a = mb_strtolower(trim($a));
        $b = mb_strtolower(trim($b));

        if ($a === '' || $b === '') {
            return 0.0;
        }

        similar_text($a, $b, $percent);

        return $percent / 100;
    }

    /**
     * @param  array<int, array{opportunity_id: int, score: float}>  $candidates  Already sorted desc.
     */
    private function maybeAutoResolve(array $candidates): ?int
    {
        if (empty($candidates)) {
            return null;
        }

        $top = $candidates[0];
        if ($top['score'] < self::AUTO_RESOLVE_SCORE) {
            return null;
        }

        if (count($candidates) === 1) {
            return $top['opportunity_id'];
        }

        $runnerUp = $candidates[1];
        if (($top['score'] - $runnerUp['score']) >= self::AUTO_RESOLVE_MARGIN) {
            return $top['opportunity_id'];
        }

        return null;
    }

    private function logSearch(
        AgentTask $task,
        ?string $clientName,
        ?string $address,
        ?string $claimNumber,
        array $candidates,
        ?int $resolvedId,
    ): void {
        $criteria = collect([
            'client_name' => $clientName,
            'address' => $address,
            'claim_number' => $claimNumber,
        ])->filter()->map(fn ($v, $k) => "{$k}=\"{$v}\"")->implode(', ');

        $summary = empty($candidates)
            ? "find_opportunity({$criteria}): no candidates found."
            : "find_opportunity({$criteria}): " . collect($candidates)
                ->map(fn (array $c) => "#{$c['opportunity_id']} (job_no={$c['job_no']}, score={$c['score']})")
                ->implode(', ')
                . ($resolvedId !== null ? " — auto-resolved to #{$resolvedId}." : ' — ambiguous/no auto-resolve.');

        AgentMessage::create(['task_id' => $task->id, 'sender' => 'agent', 'body' => $summary]);
    }
}
