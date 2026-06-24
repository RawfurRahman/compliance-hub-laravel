<?php

namespace App\Modules\Governance\Console\Commands;

use App\Modules\Governance\Jobs\SendReviewReminderJob;
use App\Modules\Governance\Models\PolicyReview;
use Illuminate\Console\Command;

class SendReviewReminders extends Command
{
    protected $signature = 'governance:send-review-reminders';
    protected $description = 'Dispatch reminders for overdue policy reviews';

    public function handle(): int
    {
        $overdueReviews = PolicyReview::with('policy')
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('due_date', '<', now())
            ->get();

        if ($overdueReviews->isEmpty()) {
            $this->info('No overdue reviews found.');
            return Command::SUCCESS;
        }

        foreach ($overdueReviews as $review) {
            SendReviewReminderJob::dispatch($review);
        }

        $this->info("Dispatched {$overdueReviews->count()} review reminder(s).");
        return Command::SUCCESS;
    }
}
