<?php

namespace App\Modules\RiskManagement\Events;

use App\Modules\RiskManagement\Models\VendorAssessment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendorAssessmentCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public VendorAssessment $assessment,
    ) {}
}
