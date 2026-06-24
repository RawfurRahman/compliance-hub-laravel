<?php

namespace App\Modules\RiskManagement\Events;

use App\Modules\RiskManagement\Models\ThirdPartyVendor;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendorRiskEscalated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ThirdPartyVendor $vendor,
        public string $previousTier,
        public string $newTier,
    ) {}
}
