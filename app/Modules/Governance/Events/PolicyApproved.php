<?php

namespace App\Modules\Governance\Events;

use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyApproval;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PolicyApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Policy $policy,
        public PolicyApproval $approval,
        public int $approverId,
    ) {}
}
