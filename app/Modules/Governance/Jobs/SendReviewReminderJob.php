<?php

namespace App\Modules\Governance\Jobs;

use App\Modules\Governance\Models\PolicyReview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReviewReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public PolicyReview $review,
    ) {}

    public function handle(): void
    {
        $daysOverdue = now()->diffInDays($this->review->due_date);

        \App\Models\ActivityLog::create([
            'user_id' => $this->review->reviewer_user_id,
            'action' => 'review_reminder_sent',
            'description' => "Reminder: Review #{$this->review->id} for policy {$this->review->policy?->policy_number} is {$daysOverdue} day(s) overdue.",
            'details' => [
                'review_id' => $this->review->id,
                'policy_id' => $this->review->policy_id,
                'days_overdue' => $daysOverdue,
                'due_date' => $this->review->due_date?->toDateString(),
            ],
            'ip_address' => request()->ip(),
        ]);
    }
}
