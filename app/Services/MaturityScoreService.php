<?php

namespace App\Services;

use App\Models\AssessmentFinding;
use App\Models\EvidenceFile;
use App\Models\Framework;
use App\Models\MaturityScoreSnapshot;
use App\Models\ProjectAssessment;
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
     * Averages the compliance percentage reported by ProjectAssessment::stats()
     * across the latest assessment of each active framework.
     */
    public function calculateControlDesignMaturity(): array
    {
        $activeFrameworkIds = Framework::where('is_active', true)->pluck('id');

        if ($activeFrameworkIds->isEmpty()) {
            return $this->result(1.0, 0, 'No active frameworks; defaulting to score 1.');
        }

        $percentages = [];

        foreach ($activeFrameworkIds as $frameworkId) {
            $assessment = ProjectAssessment::where('framework_id', $frameworkId)
                ->latest('id')
                ->first();

            if (! $assessment) {
                continue;
            }

            $percentages[] = $assessment->stats()['compliancePct'];
        }

        $sampleSize = count($percentages);

        if ($sampleSize === 0) {
            return $this->result(
                1.0,
                0,
                'Active frameworks exist but none have assessments; defaulting to score 1.'
            );
        }

        $average = array_sum($percentages) / $sampleSize;
        $score   = $this->percentageToScore($average);

        return $this->result(
            $score,
            $sampleSize,
            sprintf(
                'Averaged compliancePct across %d active framework assessment(s): %.1f%%.',
                $sampleSize,
                $average
            )
        );
    }

    /**
     * Dimension 3: Remediation velocity.
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

        $auditedControlIds = EvidenceFile::query()
            ->where('scan_status', 'clean')
            ->where('hitl_status', 'accepted')
            ->whereNotNull('framework_control_id')
            ->distinct()
            ->pluck('framework_control_id');

        $audited = 0;
        if ($auditedControlIds->isNotEmpty()) {
            $audited = AssessmentFinding::query()
                ->whereIn('framework_control_id', $auditedControlIds)
                ->count();
        }

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
     * Dimension 5: Composite score.
     *
     * Simple average of the four dimension scores, rounded to one decimal.
     */
    public function calculateCompositeScore(): array
    {
        $dimensions = [
            $this->calculateRiskManagementMaturity(),
            $this->calculateControlDesignMaturity(),
            $this->calculateRemediationVelocity(),
            $this->calculateEvidenceAuditMaturity(),
        ];

        $scores  = array_map(fn ($dimension) => $dimension['score_value'], $dimensions);
        $average = round(array_sum($scores) / count($scores), 1);

        return $this->result(
            $average,
            count($scores),
            sprintf('Average of the four dimension scores: %s.', implode(', ', $scores))
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
            MaturityScoreSnapshot::DIMENSION_RISK_MANAGEMENT     => $this->calculateRiskManagementMaturity(),
            MaturityScoreSnapshot::DIMENSION_CONTROL_DESIGN      => $this->calculateControlDesignMaturity(),
            MaturityScoreSnapshot::DIMENSION_REMEDIATION_VELOCITY => $this->calculateRemediationVelocity(),
            MaturityScoreSnapshot::DIMENSION_EVIDENCE_AUDIT      => $this->calculateEvidenceAuditMaturity(),
        ];

        // Composite is derived from the four dimensions above.
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
