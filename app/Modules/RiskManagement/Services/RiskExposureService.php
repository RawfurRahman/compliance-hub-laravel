<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\RiskExposure;
use App\Modules\RiskManagement\Models\RiskRegister;
use Illuminate\Support\Facades\Auth;

class RiskExposureService
{
    public function getForRisk(int $riskId)
    {
        return RiskExposure::where('risk_register_id', $riskId)
            ->orderByDesc('calculated_at')
            ->get();
    }

    public function calculateAndStore(RiskRegister $risk, ?float $overrideAmount = null): RiskExposure
    {
        $assetValue = (float) $risk->asset_value_bdt;
        $tv = (int) ($risk->computed_tv ?? $risk->tv_t_av);
        $lh = (int) $risk->likelihood_lh;
        $av = (int) $risk->vulnerability_level_av;

        $inherentExposure = $assetValue * ($av * $tv * $lh) / 100;

        $residualTv = (int) ($risk->computed_residual_rating
            ? ($risk->computed_residual_rating / max(1, (int) $risk->residual_lh))
            : $risk->residual_tv);
        $residualLh = (int) $risk->residual_lh;
        $residualExposure = $assetValue * ($residualTv * $residualLh) / 100;

        $financialAmount = $overrideAmount ?? $assetValue;

        return RiskExposure::create([
            'risk_register_id'  => $risk->id,
            'exposure_type'     => 'financial',
            'inherent_exposure' => round($inherentExposure, 2),
            'residual_exposure' => round($residualExposure, 2),
            'financial_amount'  => round($financialAmount, 2),
            'probability_pct'   => $lh * 20,
            'impact_rating'     => max(1, min(5, (int) ceil($tv / 2))),
            'currency'          => 'BDT',
            'calculated_at'     => now(),
            'created_by'        => Auth::id(),
        ]);
    }
}
