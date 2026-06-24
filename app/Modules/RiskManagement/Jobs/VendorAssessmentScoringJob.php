<?php

namespace App\Modules\RiskManagement\Jobs;

use App\Modules\RiskManagement\Models\VendorAssessment;
use App\Modules\RiskManagement\Services\VendorAssessmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VendorAssessmentScoringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $assessmentId,
    ) {}

    public function handle(VendorAssessmentService $service): void
    {
        $assessment = VendorAssessment::find($this->assessmentId);
        if (!$assessment) {
            return;
        }

        $service->recalculateScore($assessment);
    }
}
