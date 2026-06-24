<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\RiskRegister;
use App\Models\HeatmapConfig;

/**
 * RiskCalculationService — Phase 6
 *
 * All risk scoring logic lives here.
 *
 * Canonical Formula:
 *   tv_t_av = threat_level_t + vulnerability_level_av
 *   risk_rating_avtvlh = vulnerability_level_av * tv_t_av * likelihood_lh
 *   residual_rating = residual_tv * residual_lh
 */
class RiskCalculationService
{
    /**
     * Calculate TV (Threat + Vulnerability) score.
     */
    public function tvScore(int $threatLevel, int $vulnLevel): int
    {
        return $threatLevel + $vulnLevel;
    }

    /**
     * Calculate Inherent Risk Rating.
     */
    public function inherentScore(int $vulnLevel, int $tvScore, int $likelihood): int
    {
        return $vulnLevel * $tvScore * $likelihood;
    }

    /**
     * Calculate Residual Risk Rating.
     */
    public function residualScore(int $residualTv, int $residualLh): int
    {
        return $residualTv * $residualLh;
    }

    /**
     * Derive risk level from score using custom configuration or standard thresholds.
     */
    public function scoreToLevel(int $score): string
    {
        $config = HeatmapConfig::first();
        $critical = $config?->critical_threshold ?? 128;
        $high     = $config?->high_threshold     ?? 84;
        $medium   = $config?->medium_threshold   ?? 54;

        if ($score >= $critical) return 'Critical';
        if ($score >= $high)     return 'High';
        if ($score >= $medium)   return 'Medium';
        return 'Low';
    }

    /**
     * Risk reduction percentage.
     */
    public function riskReductionPct(int $inherent, int $residual): float
    {
        if ($inherent <= 0) return 0.0;
        return round((1 - ($residual / $inherent)) * 100, 1);
    }

    /**
     * Control effectiveness percentage.
     */
    public function controlEffectiveness(int $inherent, int $residual): float
    {
        return $this->riskReductionPct($inherent, $residual);
    }

    /**
     * Estimated financial exposure based on asset value and inherent rating score.
     */
    public function financialExposure(float $assetValueBdt, int $inherentScore): float
    {
        // Formula normalized to max scale (max score = 250 in 5x10x5)
        return round($assetValueBdt * ($inherentScore / 250), 2);
    }

    /**
     * Stamp all calculated fields onto a data array before upsert.
     */
    public function stampCalculations(array &$data): void
    {
        $threatLevel = (int) ($data['threat_level_t']          ?? 1);
        $vulnLevel   = (int) ($data['vulnerability_level_av'] ?? 1);
        $likelihood  = (int) ($data['likelihood_lh']           ?? 1);

        $residualTv  = (int) ($data['residual_tv']             ?? 1);
        $residualLh  = (int) ($data['residual_lh']             ?? 1);

        // Compute tv_t_av
        $data['tv_t_av'] = $this->tvScore($threatLevel, $vulnLevel);
        $data['computed_tv'] = $data['tv_t_av'];

        // Compute inherent score
        $data['risk_rating_avtvlh'] = $this->inherentScore($vulnLevel, $data['tv_t_av'], $likelihood);
        $data['computed_risk_rating'] = $data['risk_rating_avtvlh'];

        // Compute residual rating
        $data['residual_rating'] = $this->residualScore($residualTv, $residualLh);
        $data['computed_residual_rating'] = $data['residual_rating'];
    }
}
