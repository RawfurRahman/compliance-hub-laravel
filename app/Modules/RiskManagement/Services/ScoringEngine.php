<?php

namespace App\Modules\RiskManagement\Services;

class ScoringEngine
{
    /**
     * Calculate TV (Threat + Vulnerability) score.
     * TV = Threat Level + Vulnerability Level
     */
    public function calculateTvScore(int $threat, int $vuln): int
    {
        return $threat + $vuln;
    }

    /**
     * Calculate Inherent Risk Rating.
     * Inherent Score = Vuln Level (AV) * TV (T+AV) * Likelihood (LH)
     */
    public function calculateInherentScore(int $vuln, int $tv, int $likelihood): int
    {
        return $vuln * $tv * $likelihood;
    }

    /**
     * Calculate Cumulative Control Effectiveness.
     * Formula: 1 - product(1 - effectiveness_i / 100)
     * Returns cumulative effectiveness as a percentage (0.0 to 100.0).
     *
     * @param  array  $effectivenessScores  Array of integers/floats between 0 and 100
     */
    public function calculateCumulativeEffectiveness(array $effectivenessScores): float
    {
        if (empty($effectivenessScores)) {
            return 0.0;
        }

        $remainingInsecurity = 1.0;

        foreach ($effectivenessScores as $score) {
            $fraction = floatval($score) / 100.0;
            // Cap fraction between 0.0 and 1.0 to protect against overflow or bad entries
            $fraction = max(0.0, min(1.0, $fraction));
            $remainingInsecurity *= (1.0 - $fraction);
        }

        $cumulativeFraction = 1.0 - $remainingInsecurity;

        return round($cumulativeFraction * 100.0, 2);
    }

    /**
     * Calculate exposure value based on asset value, inherent score, and a conversion factor.
     * Exposure = Asset Value × (Inherent Score / Max Possible Score) × Financial Conversion Factor
     */
    public function calculateExposureValue(float $assetValue, int $inherentScore, float $financialFactor = 1.0): float
    {
        $maxPossibleScore = 5 * (5 + 5) * 5;
        $normalizedScore = min(1.0, $inherentScore / max(1, $maxPossibleScore));

        return round($assetValue * $normalizedScore * $financialFactor, 2);
    }

    /**
     * Calculate delta between inherent and residual score.
     */
    public function calculateDelta(int $inherentScore, int $residualScore): int
    {
        return max(0, $inherentScore - $residualScore);
    }

    /**
     * Derive Risk Level (Critical, High, Medium, Low) from rating score.
     * Classifies scores according to configuration thresholds.
     */
    public function scoreToLevel(int $score): string
    {
        $critical = config('rmm.thresholds.critical', 128);
        $high = config('rmm.thresholds.high', 84);
        $medium = config('rmm.thresholds.medium', 54);

        if ($score >= $critical) {
            return 'Critical';
        }
        if ($score >= $high) {
            return 'High';
        }
        if ($score >= $medium) {
            return 'Medium';
        }

        return 'Low';
    }
}
