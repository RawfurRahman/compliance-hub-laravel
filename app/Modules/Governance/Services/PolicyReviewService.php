<?php

namespace App\Modules\Governance\Services;

use App\Models\ActivityLog;
use App\Modules\Governance\Events\PolicyReviewCompleted;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyReview;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class PolicyReviewService
{
    public function listForPolicy(Policy $policy): Collection
    {
        return $policy->reviews()->with(['reviewer', 'policyVersion'])->orderByDesc('created_at')->get();
    }

    public function submitReview(Policy $policy, array $data): PolicyReview
    {
        if (!$policy->isUnderReview()) {
            throw new \RuntimeException('Policy must be under review to submit a review.');
        }

        $review = $policy->reviews()->create([
            'policy_version_id' => $data['policy_version_id'] ?? null,
            'reviewer_user_id' => $data['reviewer_user_id'],
            'review_type' => $data['review_type'] ?? 'scheduled',
            'status' => 'pending',
            'due_date' => $data['due_date'] ?? null,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'review_submitted',
            'description' => "Review submitted for policy {$policy->policy_number}",
            'details' => [
                'policy_id' => $policy->id,
                'review_id' => $review->id,
                'reviewer_id' => $data['reviewer_user_id'],
            ],
            'ip_address' => request()->ip(),
        ]);

        return $review->fresh()->load(['reviewer']);
    }

    public function completeReview(PolicyReview $review, string $status, ?string $comments = null): PolicyReview
    {
        if (!in_array($status, ['completed', 'overdue'])) {
            throw new \InvalidArgumentException('Review can only be completed or marked overdue.');
        }

        $review->update([
            'status' => $status,
            'comments' => $comments,
            'completed_at' => now(),
        ]);

        PolicyReviewCompleted::dispatch($review->policy, $review, Auth::id(), $status);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'policy_review_completed',
            'description' => "Review {$review->id} completed for policy {$review->policy->policy_number}",
            'details' => [
                'review_id' => $review->id,
                'policy_id' => $review->policy_id,
                'status' => $status,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $review->fresh();
    }

    public function getOverdueReviews(): Collection
    {
        return PolicyReview::with(['policy', 'reviewer'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('due_date', '<', now())
            ->get();
    }
}
