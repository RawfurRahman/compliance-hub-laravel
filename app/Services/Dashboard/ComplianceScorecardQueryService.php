<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\DashboardFilter;
use App\Models\Framework;
use App\Models\ProjectAssessment;
use App\Modules\Compliance\Models\ComplianceTestFrameworkLink;

class ComplianceScorecardQueryService extends BaseQueryService
{
    protected function domain(): string
    {
        return DashboardDomains::COMPLIANCE_SCORECARD;
    }

    protected function compute(DashboardFilter $filter): array
    {
        $query = Framework::where('is_active', true);
        if ($filter->framework) {
            $query->where('name', $filter->framework);
        }
        $activeFrameworks = $query->get();
        $activeFrameworkIds = $activeFrameworks->pluck('id');

        $latestAssessments = ProjectAssessment::with('findings')
            ->whereIn('framework_id', $activeFrameworkIds)
            ->whereIn('type', ['Gap', 'Final'])
            ->get()
            ->groupBy(fn ($a) => $a->framework_id . '|' . $a->type)
            ->map(fn ($group) => $group->sortByDesc('id')->first());

        // Pre-fetch compliance test counts per framework for test_pass_rate
        $testLinks = ComplianceTestFrameworkLink::whereIn('framework_id', $activeFrameworkIds)
            ->with('complianceTest')
            ->get()
            ->groupBy('framework_id');

        $scorecard = $activeFrameworks->map(function (Framework $framework) use ($latestAssessments, $testLinks) {
            $gapKey = $framework->id . '|Gap';
            $finalKey = $framework->id . '|Final';

            $gap = $latestAssessments->get($gapKey);
            $final = $latestAssessments->get($finalKey);

            $gapPct = $gap ? $gap->stats()['compliancePct'] : 0.0;
            $finalPct = $final ? $final->stats()['compliancePct'] : 0.0;

            $phase = $this->derivePhase($gap, $gapPct, $final, $finalPct);

            $links = $testLinks->get($framework->id, collect());
            $totalTests = $links->count();
            $passingTests = $links->filter(fn ($l) => $l->complianceTest?->status === 'Passing')->count();
            $testPassRate = $totalTests > 0 ? round(($passingTests / $totalTests) * 100, 1) : null;

            return [
                'framework' => $framework->name,
                'slug' => $framework->slug ?? null,
                'percentage' => $final ? $finalPct : $gapPct,
                'phase' => $phase,
                'fully_compliant' => $phase === 'final_done',
                'test_pass_rate' => $testPassRate,
            ];
        })->values();

        return ['scorecard' => $scorecard->toArray()];
    }

    private function derivePhase(
        ?ProjectAssessment $gap,
        float $gapPct,
        ?ProjectAssessment $final,
        float $finalPct
    ): string {
        if (!$final) {
            return $gapPct >= 100 ? 'gap_done' : 'gap_in_progress';
        }
        if ($finalPct >= 100) {
            return 'final_done';
        }
        return $finalPct > 0 ? 'final_in_progress' : 'final_pending';
    }
}
