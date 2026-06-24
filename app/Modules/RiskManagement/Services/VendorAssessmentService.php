<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\VendorAssessment;
use App\Modules\RiskManagement\Models\VendorQuestionnaireResponse;
use Illuminate\Support\Facades\Auth;

class VendorAssessmentService
{
    public function getForVendor(int $vendorId)
    {
        return VendorAssessment::where('vendor_id', $vendorId)
            ->with('responses')
            ->orderByDesc('assessment_date')
            ->get();
    }

    public function create(array $data): VendorAssessment
    {
        return VendorAssessment::create($data);
    }

    public function update(VendorAssessment $assessment, array $data): VendorAssessment
    {
        $assessment->update($data);
        return $assessment->fresh();
    }

    public function delete(VendorAssessment $assessment): void
    {
        $assessment->delete();
    }

    public function submitResponse(VendorAssessment $assessment, array $data): VendorQuestionnaireResponse
    {
        return $assessment->responses()->create($data);
    }

    public function recalculateScore(VendorAssessment $assessment): VendorAssessment
    {
        $responses = $assessment->responses;

        if ($responses->isEmpty()) {
            return $assessment;
        }

        $totalScore = 0;
        $totalMaxScore = 0;

        foreach ($responses as $response) {
            if ($response->max_score > 0) {
                $totalScore += $response->score ?? 0;
                $totalMaxScore += $response->max_score;
            }
        }

        $overallScore = $totalMaxScore > 0
            ? round(($totalScore / $totalMaxScore) * 100, 2)
            : null;

        $riskRating = $overallScore !== null
            ? $assessment->scoreToRating($overallScore)
            : null;

        $assessment->update([
            'overall_score' => $overallScore,
            'risk_rating'   => $riskRating,
        ]);

        return $assessment->fresh();
    }

    public function completeAssessment(VendorAssessment $assessment): VendorAssessment
    {
        $assessment = $this->recalculateScore($assessment);

        $assessment->update([
            'status'         => 'completed',
            'completed_date' => now(),
        ]);

        return $assessment->fresh();
    }
}
