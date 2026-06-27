<?php

namespace App\Modules\RiskManagement\Listeners;

use App\Modules\RiskManagement\Events\VendorAssessmentCompleted;
use App\Services\VendorAssessmentAnalysisService;
use Illuminate\Support\Facades\Log;

class RunVendorAiSummaryListener
{
    public function __construct(
        private VendorAssessmentAnalysisService $analysisService,
    ) {}

    public function handle(VendorAssessmentCompleted $event): void
    {
        try {
            $this->analysisService->analyze($event->assessment);
        } catch (\Exception $e) {
            Log::error('RunVendorAiSummaryListener failed: ' . $e->getMessage());
        }
    }
}
