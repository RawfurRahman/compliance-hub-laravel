<?php

namespace App\Modules\Governance\Events;

use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyVersion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PolicyPublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Policy $policy,
        public PolicyVersion $version,
        public int $publishedBy,
    ) {}
}
