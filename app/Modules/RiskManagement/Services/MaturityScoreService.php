<?php

namespace App\Modules\RiskManagement\Services;

use App\Models\AssessmentFinding;
use App\Models\EvidenceFile;
use App\Models\Framework;
use App\Models\MaturityScoreSnapshot;
use App\Models\FrameworkControl;
use App\Models\ProjectAssessment;
use App\Modules\RiskManagement\Models\RiskControlMapping;
use Illuminate\Support\Carbon;

/**
 * Calculates GRC maturity scores across four dimensions and persists daily
 * snapshots.
 *
 * Scoring scale (percentage -> 1..5 score):
 *   0-25%   = 1
 *   26-50%  = 2
 *   51-70%  = 3
 *   71-90%  = 4
 *   91-100% = 5
 *
 * IMPORTANT - schema reconciliation notes:
 * The original spec references several attributes that do not exist on the
 * real models. Each method below maps the spec to the closest real columns
 * and documents the assumption in its calculation_notes payload:
 *  - "accept/reject decision"  -> AssessmentFinding.status moved off 'Open'
 *  - "proposed fix"            -> AssessmentFinding.recommendation (non-empty)
 *  - "due date"               -> AssessmentFinding.due_date (added by migration)
 *  - "fixed/compliant"        -> is_compliant === true OR status === 'Closed'
 *  - evidence scan + review   -> EvidenceFile.scan_status='clean'
 *                                AND hitl_status='accepted', matched to a
 *                                finding via framework_control_id.
 */
class MaturityScoreService
{
    /**
     * Dimension 1: Risk management maturity.
     *
     * Percentage of findings that have BOTH a recorded accept/reject decision
     * (status is no longer 'Open') AND a non-empty proposed fix (recommendation).
     */
    public function calculateRiskManagementMaturity(): array
    {
        $total = AssessmentFinding::count();

        if ($total === 0) {
            return $this->result(1.0, 0, 'No findings exist; defaulting to score 1.');
        }

        $qualified = AssessmentFinding::query()
            ->where('status', '!=', 'Open')
            ->whereNotNull('recommendation')
            ->where('recommendation', '!=', '')
            ->count();

        $percentage = ($qualified / $total) * 100;
        $score      = $this->percentageToScore($percentage);

        return $this->result(
            $score,
            $total,
            sprintf(
                '%d of %d findings have a decision (status != Open) and a non-empty recommendation (%.1f%%).',
                $qualified,
                $total,
                $percentage
            )
        );
    }

    /**
     * Dimension 2: Control design maturity.
     *
     * Blends the compliance percentage reported by ProjectAssessment::stats()
     * with the average effectiveness of mapped controls from RiskControlMapping
     * for each active framework's latest assessment.
     */
    public function calculateControlDesignMaturity(): array
    {
        $activeFrameworkIds = Framework::where('is_active', true)->pluck('id');

        if ($activeFrameworkIds->isEmpty()) {
            return $this->result(1.0, 0, 'No active frameworks; defaulting to score 1.');
        }

        $blendedScores = [];

        $assessments = ProjectAssessment::with('findings')
            ->whereIn('framework_id', $activeFrameworkIds)
            ->get()
            ->groupBy('framework_id')
            ->map(function ($group) {
                return $group->sortByDesc('id')->first();
            })
            ->filter();

        foreach ($assessments as $assessment) {
            $applicableFindings = $assessment->findings->filter(function ($finding) {
                return $finding->is_applicable !== false && $finding->is_applicable !== 0 && $finding->is_applicable !== '0';
            });

            $assessment->setRelation('findings', $applicableFindings);

            $compliancePct = $assessment->stats()['compliancePct'];

            // Calculate average effectiveness of mapped controls for this assessment
            $frameworkControlIds = $applicableFindings
                ->pluck('framework_control_id')
                ->filter()
                ->unique();

            $avgEffectiveness = null;

            if ($frameworkControlIds->isNotEmpty()) {
                $avgEffectiveness = RiskControlMapping::whereIn('framework_control_id', $frameworkControlIds)
                    ->where('mapping_status', 'confirmed')
                    ->avg('effectiveness');
            }

            $blended = $avgEffectiveness !== null
                ? ($compliancePct + (float) $avgEffectiveness) / 2
                : $compliancePct;

            $blendedScores[] = $blended;
        }

        $sampleSize = count($blendedScores);

        if ($sampleSize === 0) {
            return $this->result(
                1.0,
                0,
                'Active frameworks exist but none have assessments; defaulting to score 1.'
            );
        }

        $average = array_sum($blendedScores) / $sampleSize;
        $score   = $this->percentageToScore($average);

        return $this->result(
            $score,
            $sampleSize,
            sprintf(
                'Blended compliance (%.1f%%) with avg mapped control effectiveness across %d assessment(s).',
                $average,
                $sampleSize
            )
        );
    }

    /**
     * Dimension 3: Control coverage maturity.
     *
     * Percentage of framework controls across all active frameworks that have
     * at least one confirmed RiskControlMapping. Higher coverage indicates
     * better integration between risk management and control frameworks.
     */
    public function calculateControlCoverageMaturity(): array
    {
        $activeIds = Framework::where('is_active', true)->pluck('id');

        if ($activeIds->isEmpty()) {
            return $this->result(1.0, 0, 'No active frameworks; defaulting to score 1.');
        }

        $totalControls = FrameworkControl::whereIn('framework_id', $activeIds)->count();

        if ($totalControls === 0) {
            return $this->result(1.0, 0, 'No framework controls exist; defaulting to score 1.');
        }

        $mappedControlIds = RiskControlMapping::where('mapping_status', 'confirmed')
            ->whereHas('frameworkControl', function ($query) use ($activeIds) {
                $query->whereIn('framework_id', $activeIds);
            })
            ->distinct('framework_control_id')
            ->count('framework_control_id');

        $percentage = ($mappedControlIds / $totalControls) * 100;
        $score      = $this->percentageToScore($percentage);

        return $this->result(
            $score,
            $totalControls,
            sprintf(
                '%d of %d framework controls have confirmed risk mappings (%.1f%% coverage).',
                $mappedControlIds,
                $totalControls,
                $percentage
            )
        );
    }

    /**
     * Dimension 4: Remediation velocity.
     *
     * Of the findings whose due_date has already passed, the percentage that
     * are now fixed (is_compliant true OR status 'Closed').
     */
    public function calculateRemediationVelocity(): array
    {
        $today = Carbon::today();

        $overdueQuery = AssessmentFinding::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today);

        $overdueTotal = (clone $overdueQuery)->count();

        if ($overdueTotal === 0) {
            return $this->result(
                1.0,
                0,
                'No findings are past their due date; defaulting to score 1.'
            );
        }

        $remediated = (clone $overdueQuery)
            ->where(function ($query) {
                $query->where('is_compliant', true)
                    ->orWhere('status', 'Closed');
            })
            ->count();

        $percentage = ($remediated / $overdueTotal) * 100;
        $score      = $this->percentageToScore($percentage);

        return $this->result(
            $score,
            $overdueTotal,
            sprintf(
                '%d of %d past-due findings are fixed (%.1f%%).',
                $remediated,
                $overdueTotal,
                $percentage
            )
        );
    }

    /**
     * Dimension 4: Evidence audit maturity.
     *
     * Percentage of findings whose framework control has at least one evidence
     * file that passed a virus scan (scan_status='clean') AND was reviewed and
     * accepted by a human (hitl_status='accepted').
     */
    public function calculateEvidenceAuditMaturity(): array
    {
        $total = AssessmentFinding::count();

        if ($total === 0) {
            return $this->result(1.0, 0, 'No findings exist; defaulting to score 1.');
        }

        $audited = AssessmentFinding::query()
            ->whereHas('evidence', function ($query) {
                $query->join('evidence_files', 'evidence.path', '=', 'evidence_files.file_path')
                    ->where('evidence_files.scan_status', 'clean')
                    ->where('evidence_files.hitl_status', 'accepted');
            })
            ->count();

        $percentage = ($audited / $total) * 100;
        $score      = $this->percentageToScore($percentage);

        return $this->result(
            $score,
            $total,
            sprintf(
                '%d of %d findings have clean, human-accepted evidence (%.1f%%).',
                $audited,
                $total,
                $percentage
            )
        );
    }

    /**
     * Dimension 6 (Composite): Composite score.
     *
     * Simple average of all five dimension scores, rounded to one decimal.
     */
    public function calculateCompositeScore(): array
    {
        $dimensions = [
            $this->calculateRiskManagementMaturity(),
            $this->calculateControlDesignMaturity(),
            $this->calculateControlCoverageMaturity(),
            $this->calculateRemediationVelocity(),
            $this->calculateEvidenceAuditMaturity(),
        ];

        $scores  = array_map(fn ($dimension) => $dimension['score_value'], $dimensions);
        $average = round(array_sum($scores) / count($scores), 1);

        return $this->result(
            $average,
            count($scores),
            sprintf('Average of the five dimension scores: %s.', implode(', ', $scores))
        );
    }

    /**
     * Persist all four dimension scores plus the composite for today.
     *
     * @return \Illuminate\Support\Collection<int, MaturityScoreSnapshot>
     */
    public function snapshotToday()
    {
        $today = Carbon::today();

        $payloads = [
            MaturityScoreSnapshot::DIMENSION_RISK_MANAGEMENT      => $this->calculateRiskManagementMaturity(),
            MaturityScoreSnapshot::DIMENSION_CONTROL_DESIGN       => $this->calculateControlDesignMaturity(),
            MaturityScoreSnapshot::DIMENSION_CONTROL_COVERAGE     => $this->calculateControlCoverageMaturity(),
            MaturityScoreSnapshot::DIMENSION_REMEDIATION_VELOCITY => $this->calculateRemediationVelocity(),
            MaturityScoreSnapshot::DIMENSION_EVIDENCE_AUDIT       => $this->calculateEvidenceAuditMaturity(),
        ];

        // Composite is derived from the five dimensions above.
        $payloads[MaturityScoreSnapshot::DIMENSION_COMPOSITE] = $this->calculateCompositeScore();

        return collect($payloads)->map(function (array $payload, string $dimension) use ($today) {
            return MaturityScoreSnapshot::updateOrCreate(
                [
                    'snapshot_date' => $today,
                    'dimension'     => $dimension,
                ],
                [
                    'score_value'       => $payload['score_value'],
                    'sample_size'       => $payload['sample_size'],
                    'calculation_notes' => $payload['calculation_notes'],
                ]
            );
        })->values();
    }

    /**
     * Convert a 0-100 percentage into the 1-5 maturity scale.
     */
    protected function percentageToScore(float $percentage): float
    {
        return match (true) {
            $percentage <= 25 => 1.0,
            $percentage <= 50 => 2.0,
            $percentage <= 70 => 3.0,
            $percentage <= 90 => 4.0,
            default           => 5.0,
        };
    }

    /**
     * Normalise a calculation result.
     */
    protected function result(float $score, int $sampleSize, string $notes): array
    {
        return [
            'score_value'       => $score,
            'sample_size'       => $sampleSize,
            'calculation_notes' => $notes,
        ];
    }
}
