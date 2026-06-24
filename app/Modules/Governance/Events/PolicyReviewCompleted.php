<?php

namespace App\Modules\Governance\Events;

use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyReview;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PolicyReviewCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Policy $policy,
        public PolicyReview $review,
        public int $reviewerId,
        public string $status,
    ) {}
}
