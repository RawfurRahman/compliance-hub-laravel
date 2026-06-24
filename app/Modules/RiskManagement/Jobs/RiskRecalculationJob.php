<?php

namespace App\Modules\RiskManagement\Jobs;

use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Services\RiskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RiskRecalculationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $riskId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $riskId)
    {
        $this->riskId = $riskId;
    }

    /**
     * Execute the job.
     */
    public function handle(RiskService $service): void
    {
        $risk = RiskRegister::find($this->riskId);
        if (!$risk) {
            return;
        }

        $service->recalculateRisk($risk);
        
        if ($risk->project_id) {
            $service->updateHeatmap($risk->project_id);
        }
    }
}
