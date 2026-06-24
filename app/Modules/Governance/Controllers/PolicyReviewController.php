<?php

namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyReview;
use App\Modules\Governance\Requests\SubmitReviewRequest;
use App\Modules\Governance\Services\PolicyReviewService;
use Illuminate\Http\Request;

class PolicyReviewController extends Controller
{
    public function __construct(
        private PolicyReviewService $reviewService,
    ) {}

    public function index(Policy $policy)
    {
        $reviews = $this->reviewService->listForPolicy($policy);

        return response()->json(['data' => $reviews]);
    }

    public function store(SubmitReviewRequest $request, Policy $policy)
    {
        $this->authorize('review', $policy);

        $review = $this->reviewService->submitReview($policy, $request->validated());

        return response()->json(['data' => $review, 'message' => 'Review submitted.'], 201);
    }

    public function update(Request $request, Policy $policy, PolicyReview $review)
    {
        if ($review->policy_id !== $policy->id) {
            abort(404);
        }

        $request->validate([
            'status' => 'required|in:completed,overdue',
            'comments' => 'nullable|string|max:2000',
        ]);

        $review = $this->reviewService->completeReview($review, $request->status, $request->comments);

        return response()->json(['data' => $review, 'message' => 'Review completed.']);
    }
}
