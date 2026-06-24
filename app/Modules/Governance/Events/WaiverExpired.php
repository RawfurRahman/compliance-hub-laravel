<?php

namespace App\Modules\Governance\Events;

use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyWaiver;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaiverExpired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PolicyWaiver $waiver,
        public Policy $policy,
    ) {}
}
