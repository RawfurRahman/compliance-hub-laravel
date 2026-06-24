<?php

namespace App\Modules\Governance\Events;

use App\Modules\Governance\Models\Policy;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PolicySubmittedForReview
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Policy $policy,
        public int $submittedBy,
        public ?string $comment = null,
    ) {}
}
