<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\FinancialExposureMetric;
use App\Modules\RiskManagement\Models\RiskRegister;
use Illuminate\Support\Collection;

/**
 * FinancialExposureService
 *
 * Computes financial exposure metrics from source data (the workbook risk
 * register). The dashboard is just a rendering layer over this engine.
 *
 * Core quantitative model (per risk):
 *   asset_value = risk.asset_value_bdt                 (workbook column)
 *   severity    = inherent_score / max_score           (0-1 exposure factor)
 *   SLE         = asset_value * exposure_factor(severity)
 *   ARO         = annualized rate of occurrence derived from likelihood (1-5)
 *   ALE         = SLE * ARO
 *
 * Derived costs:
 *   expected_remediation_cost     = SLE * remediation_cost_ratio
 *   business_interruption_impact  = asset_value * bi_factor * exposure_factor
 *   portfolio_exposure            = sum(residual-weighted ALE) across risks
 *
 * All monetary outputs use consistent 2dp rounding.
 */
class FinancialExposureService
{
    /** Max inherent score (5 * (5+5) * 5), used to normalise severity. */
    private const MAX_SCORE = 250;

    /** Likelihood (1-5) -> annualized rate of occurrence (events / year). */
    private const ARO_BY_LIKELIHOOD = [
        1 => 0.1,
        2 => 0.25,
        3 => 0.5,
        4 => 1.0,
        5 => 2.0,
    ];

    public function __construct(
        private float $remediationCostRatio = 0.15,
        private float $businessInterruptionFactor = 0.30,
    ) {
    }

    public function getSnapshot(): array
    {
        return RiskRegister::query()
            ->get()
            ->map(function ($risk) {
                $assetValue = (float) $risk->asset_value_bdt;
                $inherent = (int) ($risk->computed_risk_rating ?? $risk->risk_rating_avtvlh);
                $likelihood = (int) $risk->likelihood_lh;

                $exposureFactor = $this->exposureFactor($inherent);
                $sle = $assetValue * $exposureFactor;
                $aro = self::ARO_BY_LIKELIHOOD[$likelihood] ?? 0.5;
                $ale = $sle * $aro;

                return [
                    'risk_register_id' => $risk->id,
                    'ale_value' => $ale,
                    'created_at' => now(),
                ];
            })
            ->toArray();
    }

    /**
     * Compute the full exposure profile for a single risk.
     *
     * @return array<string,mixed>
     */
    public function forRisk(RiskRegister $risk): array
    {
        $assetValue = (float) $risk->asset_value_bdt;
        $inherent = (int) ($risk->computed_risk_rating ?? $risk->risk_rating_avtvlh);
        $residual = (int) ($risk->computed_residual_rating ?? $risk->residual_rating);
        $likelihood = (int) $risk->likelihood_lh;

        $exposureFactor = $this->exposureFactor($inherent);
        $residualFactor = $this->exposureFactor($residual);

        $sle = $assetValue * $exposureFactor;
        $aro = self::ARO_BY_LIKELIHOOD[$likelihood] ?? 0.5;
        $ale = $sle * $aro;

        // Portfolio contribution uses the residual exposure factor: the loss we
        // still carry after controls.
        $residualAle = $assetValue * $residualFactor * $aro;

        $remediationCost = $sle * $this->remediationCostRatio;
        $businessInterruption = $assetValue * $this->businessInterruptionFactor * $exposureFactor;

        return [
            'risk_register_id'             => $risk->id,
            'serial_no'                    => $risk->serial_no,
            'category'                     => $risk->category,
            'asset_value'                  => round($assetValue, 2),
            'single_loss_expectancy'       => round($sle, 2),
            'annualized_rate_of_occurrence' => $aro,
            'annualized_loss_expectancy'   => round($ale, 2),
            'expected_remediation_cost'    => round($remediationCost, 2),
            'business_interruption_impact' => round($businessInterruption, 2),
            'residual_annualized_loss'     => round($residualAle, 2),
        ];
    }

    /**
     * Portfolio + category rollups for a project (or all projects when null).
     *
     * @return array<string,mixed>
     */
    public function forProject(?int $projectId = null): array
    {
        $query = RiskRegister::query();
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }
        $risks = $query->get();

        $perRisk = $risks->map(fn (RiskRegister $r) => $this->forRisk($r));

        $portfolio = $this->aggregate($perRisk);
        $portfolio['portfolio_exposure'] = round($perRisk->sum('residual_annualized_loss'), 2);

        $byCategory = $perRisk
            ->groupBy(fn ($row) => $row['category'] ?: 'Uncategorized')
            ->map(function (Collection $group, string $category) {
                $agg = $this->aggregate($group);
                $agg['category'] = $category;
                $agg['portfolio_exposure'] = round($group->sum('residual_annualized_loss'), 2);
                return $agg;
            })
            ->values();

        return [
            'project_id' => $projectId,
            'portfolio'  => $portfolio,
            'categories' => $byCategory,
            'risks'      => $perRisk->values(),
        ];
    }

    /**
     * Query historical exposure metric snapshots for trend charts.
     *
     * @return \Illuminate\Support\Collection<int,array{date:string,sle:float,ale:float,portfolio_exposure:float}>
     */
    public function getTrendData(?int $projectId = null, ?string $dateFrom = null, ?string $dateTo = null): \Illuminate\Support\Collection
    {
        $query = FinancialExposureMetric::query()
            ->where('scope', 'portfolio')
            ->orderBy('calculated_at');

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }
        if ($dateFrom) {
            $query->whereDate('calculated_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('calculated_at', '<=', $dateTo);
        }

        return $query->get()->map(fn (FinancialExposureMetric $m) => [
            'id'                 => $m->id,
            'date'               => $m->calculated_at->toDateString(),
            'sle'                => (float) $m->single_loss_expectancy,
            'ale'                => (float) $m->annualized_loss_expectancy,
            'portfolio_exposure' => (float) $m->portfolio_exposure,
            'risk_count'         => $m->risk_count,
            'remediation_cost'   => (float) $m->expected_remediation_cost,
        ]);
    }

    /**
     * Compute and persist portfolio + category exposure metrics for trend
     * reporting. Returns the persisted portfolio metric.
     */
    public function snapshot(?int $projectId = null): FinancialExposureMetric
    {
        $profile = $this->forProject($projectId);
        $portfolio = $profile['portfolio'];

        $metric = FinancialExposureMetric::create([
            'project_id'                   => $projectId,
            'scope'                        => 'portfolio',
            'category'                     => null,
            'risk_count'                   => $portfolio['risk_count'],
            'single_loss_expectancy'       => $portfolio['single_loss_expectancy'],
            'annualized_loss_expectancy'   => $portfolio['annualized_loss_expectancy'],
            'expected_remediation_cost'    => $portfolio['expected_remediation_cost'],
            'business_interruption_impact' => $portfolio['business_interruption_impact'],
            'portfolio_exposure'           => $portfolio['portfolio_exposure'],
            'breakdown'                    => $profile['risks'],
            'calculated_at'                => now(),
        ]);

        foreach ($profile['categories'] as $category) {
            FinancialExposureMetric::create([
                'project_id'                   => $projectId,
                'scope'                        => 'category:' . $category['category'],
                'category'                     => $category['category'],
                'risk_count'                   => $category['risk_count'],
                'single_loss_expectancy'       => $category['single_loss_expectancy'],
                'annualized_loss_expectancy'   => $category['annualized_loss_expectancy'],
                'expected_remediation_cost'    => $category['expected_remediation_cost'],
                'business_interruption_impact' => $category['business_interruption_impact'],
                'portfolio_exposure'           => $category['portfolio_exposure'],
                'breakdown'                    => null,
                'calculated_at'                => now(),
            ]);
        }

        return $metric;
    }

    /* ------------------------------------------------------------------ */
    /* Internal helpers                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Aggregate a collection of per-risk exposure rows into a rollup.
     *
     * @param Collection<int,array<string,mixed>> $rows
     * @return array<string,mixed>
     */
    private function aggregate(Collection $rows): array
    {
        return [
            'risk_count'                   => $rows->count(),
            'single_loss_expectancy'       => round($rows->sum('single_loss_expectancy'), 2),
            'annualized_loss_expectancy'   => round($rows->sum('annualized_loss_expectancy'), 2),
            'expected_remediation_cost'    => round($rows->sum('expected_remediation_cost'), 2),
            'business_interruption_impact' => round($rows->sum('business_interruption_impact'), 2),
        ];
    }

    /**
     * Severity score (0-250) -> exposure factor (0-1). The fraction of asset
     * value considered exposed at a given severity.
     */
    private function exposureFactor(int $score): float
    {
        return max(0.0, min(1.0, $score / self::MAX_SCORE));
    }
}
