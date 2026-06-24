<?php

namespace App\Modules\Compliance\Events;

use App\Models\AssessmentFinding;
use App\Modules\RiskManagement\Models\RiskTreatmentPlan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemediationPlanCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public RiskTreatmentPlan $plan,
        public AssessmentFinding $finding,
    ) {}
}
