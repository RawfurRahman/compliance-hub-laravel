<?php

namespace App\Modules\RiskManagement\Events;

use App\Modules\RiskManagement\Models\RiskRegister;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiskExposureRecalculated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public RiskRegister $risk,
        public ?float $previousExposure,
        public float $newExposure,
    ) {}
}
