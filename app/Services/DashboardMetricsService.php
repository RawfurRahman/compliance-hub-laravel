<?php

namespace App\Services;

use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\Project;
use App\Models\ProjectAssessment;
use Illuminate\Support\Collection;

/**
 * Aggregation layer for the analytics dashboard.
 *
 * This service owns ALL dashboard calculation logic so the controller can
 * stay a thin pass-through. Everything here reads from the real unified
 * engine (ProjectAssessment::stats() + AssessmentFinding columns) and the
 * MaturityScoreService. No new persistence or business rules are introduced.
 *
 * Schema reconciliation notes (mirrors MaturityScoreService conventions):
 *  - There is no dedicated "department" column on assessment findings. The
 *    closest real grouping axis on the unified engine is the control's
 *    framework domain (FrameworkControl.domain), so "by department" methods
 *    group by domain and label it accordingly.
 *  - There is no separate inherent/residual score column. Inherent risk is
 *    derived from the control's risk_rating; residual risk is the same
 *    rating restricted to findings that are NOT yet compliant (i.e. the risk
 *    that remains after remediation/controls are applied).
 *
 * The Gap -> Final phase rule is intentionally NOT re-implemented here. It is
 * read straight from the engine: a framework's Gap assessment is only
 * "done" when its compliancePct hits exactly 100, which is the same gate
 * AssessmentService::syncFinding() uses to create/destroy the Final
 * assessment. This guarantees the scorecard cannot contradict the rule that
 * blocks opening a Final before Gap == 100%.
 */
class DashboardMetricsService
{
    /** Risk weights used to turn risk_rating into a comparable numeric score. */
    private const RISK_WEIGHTS = [
        'High'   => 3,
        'Medium' => 2,
        'Low'    => 1,
        'None'   => 0,
    ];

    public function __construct(
        private MaturityScoreService $maturityScoreService
    ) {
    }

    /**
     * Headline counters for the top of the dashboard.
     */
    public function kpis(): array
    {
        $findings = AssessmentFinding::query();

        $total     = (clone $findings)->count();
        $compliant = (clone $findings)->where('is_compliant', true)->count();
        $open      = (clone $findings)->where('status', 'Open')->count();
        $overdue   = (clone $findings)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->where('is_compliant', false)
            ->count();

        return [
            'projects'         => Project::count(),
            'frameworks'       => Framework::where('is_active', true)->count(),
            'total_controls'   => $total,
            'compliant'        => $compliant,
            'open_findings'    => $open,
            'overdue_findings' => $overdue,
            'compliance_pct'   => $total > 0 ? round($compliant / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * Risk heatmap cells. Likelihood is approximated by remediation status
     * (Open = most likely to bite, Closed = least), impact by risk_rating.
     * Returns one cell per (likelihood, impact) pair with a count.
     */
    public function heatmap(): array
    {
        $likelihoodAxis = ['Open', 'In Progress', 'Closed'];
        $impactAxis     = ['Low', 'Medium', 'High'];

        $rows = AssessmentFinding::query()
            ->selectRaw('status, risk_rating, COUNT(*) as aggregate')
            ->whereIn('status', $likelihoodAxis)
            ->whereIn('risk_rating', $impactAxis)
            ->groupBy('status', 'risk_rating')
            ->get()
            ->keyBy(fn ($row) => $row->status . '|' . $row->risk_rating);

        $cells = [];
        foreach ($likelihoodAxis as $likelihood) {
            foreach ($impactAxis as $impact) {
                $key = $likelihood . '|' . $impact;
                $cells[] = [
                    'likelihood' => $likelihood,
                    'impact'     => $impact,
                    'count'      => (int) ($rows->get($key)->aggregate ?? 0),
                ];
            }
        }

        return $cells;
    }

    /**
     * Highest-risk open findings, ranked. Capped to keep the chart readable.
     */
    public function topRisks(int $limit = 10): Collection
    {
        return AssessmentFinding::query()
            ->with(['frameworkControl', 'projectAssessment.framework', 'projectAssessment.project'])
            ->where('is_compliant', false)
            ->where('risk_rating', '!=', 'None')
            ->get()
            ->sortByDesc(fn ($f) => self::RISK_WEIGHTS[$f->risk_rating] ?? 0)
            ->take($limit)
            ->values()
            ->map(fn ($f) => [
                'id'         => $f->id,
                'control'    => $f->frameworkControl?->control_id ?? '',
                'title'      => $f->frameworkControl?->control_name ?: ($f->observation ?? ''),
                'framework'  => $f->projectAssessment?->framework?->name ?? '',
                'project'    => $f->projectAssessment?->project?->name ?? '',
                'risk'       => $f->risk_rating,
                'risk_score' => self::RISK_WEIGHTS[$f->risk_rating] ?? 0,
            ]);
    }

    /**
     * Inherent vs residual risk per domain ("department" axis).
     * Inherent = total weighted risk across all findings in the domain.
     * Residual = weighted risk across findings still non-compliant.
     */
    public function inherentVsResidualByDept(): Collection
    {
        return $this->findingsGroupedByDomain()
            ->map(function (Collection $group, string $domain) {
                $inherent = $group->sum(fn ($f) => self::RISK_WEIGHTS[$f->risk_rating] ?? 0);
                $residual = $group->where('is_compliant', false)
                    ->sum(fn ($f) => self::RISK_WEIGHTS[$f->risk_rating] ?? 0);

                return [
                    'department' => $domain,
                    'inherent'   => $inherent,
                    'residual'   => $residual,
                ];
            })
            ->values();
    }

    /**
     * Control effectiveness split. A control is "effective" when compliant,
     * "partial" when actively in progress, "ineffective" otherwise.
     */
    public function controlEffectiveness(): array
    {
        $base = AssessmentFinding::query();

        $effective   = (clone $base)->where('is_compliant', true)->count();
        $partial     = (clone $base)->where('is_compliant', false)
            ->where('status', 'In Progress')->count();
        $ineffective = (clone $base)->where('is_compliant', false)
            ->where('status', '!=', 'In Progress')->count();

        return [
            'effective'   => $effective,
            'partial'     => $partial,
            'ineffective' => $ineffective,
        ];
    }

    /**
     * Per-framework compliance percentage AND lifecycle phase.
     *
     * Phase is derived from the SAME rule the engine enforces:
     *  - No Gap assessment yet          -> gap_in_progress (nothing started)
     *  - Gap exists but compliancePct<100 -> gap_in_progress
     *  - Gap compliancePct == 100, no Final -> gap_done (Final may now open)
     *  - Final exists, compliancePct == 0   -> final_pending
     *  - Final exists, 0 < pct < 100        -> final_in_progress
     *  - Final exists, compliancePct == 100 -> final_done
     *
     * The reported `percentage` is the phase-appropriate compliance figure
     * (Final once it exists, otherwise Gap). `fully_compliant` is true ONLY
     * when the framework has reached final_done, so a framework still in the
     * Gap phase can never read as fully compliant regardless of its raw Gap
     * percentage.
     */
    public function complianceScorecard(): Collection
    {
        return Framework::where('is_active', true)
            ->get()
            ->map(function (Framework $framework) {
                $gap = $this->latestAssessment($framework->id, 'Gap');
                $final = $this->latestAssessment($framework->id, 'Final');

                $gapPct   = $gap ? $gap->stats()['compliancePct'] : 0.0;
                $finalPct = $final ? $final->stats()['compliancePct'] : 0.0;

                $phase = $this->derivePhase($gap, $gapPct, $final, $finalPct);

                return [
                    'framework'       => $framework->name,
                    'slug'            => $framework->slug ?? null,
                    'percentage'      => $final ? $finalPct : $gapPct,
                    'phase'           => $phase,
                    'fully_compliant' => $phase === 'final_done',
                ];
            })
            ->values();
    }

    /**
     * Overall maturity composite plus the four underlying dimension scores
     * (1-5 scale), straight from MaturityScoreService.
     */
    public function maturityScore(): array
    {
        return [
            'composite'           => $this->maturityScoreService->calculateCompositeScore()['score_value'],
            'risk_management'     => $this->maturityScoreService->calculateRiskManagementMaturity()['score_value'],
            'control_design'      => $this->maturityScoreService->calculateControlDesignMaturity()['score_value'],
            'remediation_velocity' => $this->maturityScoreService->calculateRemediationVelocity()['score_value'],
            'evidence_audit'      => $this->maturityScoreService->calculateEvidenceAuditMaturity()['score_value'],
        ];
    }

    /**
     * Count and weighted risk score of open findings per domain ("department").
     */
    public function riskByDepartment(): Collection
    {
        return $this->findingsGroupedByDomain()
            ->map(function (Collection $group, string $domain) {
                $open = $group->where('is_compliant', false);

                return [
                    'department' => $domain,
                    'open_count' => $open->count(),
                    'risk_score' => $open->sum(fn ($f) => self::RISK_WEIGHTS[$f->risk_rating] ?? 0),
                ];
            })
            ->values();
    }

    /**
     * Issue/remediation status breakdown across all findings.
     */
    public function issuesAndRemediation(): array
    {
        $base = AssessmentFinding::query();

        return [
            'open'        => (clone $base)->where('status', 'Open')->count(),
            'in_progress' => (clone $base)->where('status', 'In Progress')->count(),
            'closed'      => (clone $base)->where('status', 'Closed')->count(),
            'overdue'     => (clone $base)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now())
                ->where('is_compliant', false)
                ->count(),
        ];
    }

    /**
     * Split of risk treatment: accepted (closed but not compliant),
     * mitigated (compliant) and open (everything still being worked).
     */
    public function riskAcceptanceSplit(): array
    {
        $base = AssessmentFinding::query();

        $mitigated = (clone $base)->where('is_compliant', true)->count();
        $accepted  = (clone $base)->where('is_compliant', false)
            ->where('status', 'Closed')->count();
        $open      = (clone $base)->where('is_compliant', false)
            ->where('status', '!=', 'Closed')->count();

        return [
            'accepted'  => $accepted,
            'mitigated' => $mitigated,
            'open'      => $open,
        ];
    }

    /* ----------------------------------------------------------------- *
     *  Internal helpers
     * ----------------------------------------------------------------- */

    /**
     * Derive lifecycle phase. Pure read of the engine's 100% gate.
     */
    private function derivePhase(
        ?ProjectAssessment $gap,
        float $gapPct,
        ?ProjectAssessment $final,
        float $finalPct
    ): string {
        if (!$final) {
            // A Final cannot exist until Gap == 100%, so anything without a
            // Final is still in the Gap phase (done only once Gap hits 100).
            return $gapPct >= 100 ? 'gap_done' : 'gap_in_progress';
        }

        if ($finalPct >= 100) {
            return 'final_done';
        }

        return $finalPct > 0 ? 'final_in_progress' : 'final_pending';
    }

    /**
     * Latest assessment of a given type for a framework, findings eager-loaded
     * so stats() does not trigger N+1 queries.
     */
    private function latestAssessment(int $frameworkId, string $type): ?ProjectAssessment
    {
        return ProjectAssessment::with('findings')
            ->where('framework_id', $frameworkId)
            ->where('type', $type)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * All findings grouped by their control domain (the "department" axis).
     */
    private function findingsGroupedByDomain(): Collection
    {
        return AssessmentFinding::query()
            ->with('frameworkControl')
            ->get()
            ->groupBy(fn ($f) => $f->frameworkControl?->domain ?: 'Uncategorized');
    }
}
