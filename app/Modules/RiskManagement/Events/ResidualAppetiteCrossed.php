<?php

namespace App\Modules\RiskManagement\Events;

use App\Modules\RiskManagement\Models\RiskRegister;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a risk's residual score crosses the appetite threshold in either
 * direction (into or out of appetite). Lets the platform alert, escalate or
 * re-open acceptance workflows.
 */
class ResidualAppetiteCrossed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param RiskRegister $risk
     * @param string $previousStatus within_appetite | exceeds_appetite | unknown
     * @param string $newStatus      within_appetite | exceeds_appetite | unknown
     * @param int $residualScore     The residual score that triggered the crossing.
     */
    public function __construct(
        public RiskRegister $risk,
        public string $previousStatus,
        public string $newStatus,
        public int $residualScore,
    ) {}
}
