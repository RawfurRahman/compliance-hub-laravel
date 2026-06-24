<?php

namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Requests\StorePolicyRequest;
use App\Modules\Governance\Requests\UpdatePolicyRequest;
use App\Modules\Governance\Services\PolicyService;
use App\Modules\Governance\Services\PolicyVersionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PolicyController extends Controller
{
    public function __construct(
        private PolicyService $policyService,
        private PolicyVersionService $versionService,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'domain_id', 'owner_user_id', 'search']);
        $policies = $this->policyService->list($filters);

        if ($request->expectsJson()) {
            return response()->json(['data' => $policies]);
        }

        return view('governance.policies.index', compact('policies'));
    }

    public function create()
    {
        return view('governance.policies.form', ['policy' => null]);
    }

    public function store(StorePolicyRequest $request)
    {
        $policy = $this->policyService->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['data' => $policy, 'message' => 'Policy created.'], 201);
        }

        return redirect()
            ->route('governance.policies.show', $policy)
            ->with('success', "Policy {$policy->policy_number} created.");
    }

    public function show(Policy $policy)
    {
        $policy->load(['domain', 'ownerUser', 'versions', 'reviews.reviewer', 'approvals.approver', 'ownershipMatrix.user', 'stakeholders.user']);

        return view('governance.policies.show', compact('policy'));
    }

    public function edit(Policy $policy)
    {
        $this->authorize('update', $policy);

        return view('governance.policies.form', compact('policy'));
    }

    public function update(UpdatePolicyRequest $request, Policy $policy)
    {
        $this->authorize('update', $policy);

        $policy = $this->policyService->update($policy, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['data' => $policy, 'message' => 'Policy updated.']);
        }

        return redirect()
            ->route('governance.policies.show', $policy)
            ->with('success', "Policy {$policy->policy_number} updated.");
    }

    public function destroy(Request $request, Policy $policy)
    {
        $this->authorize('delete', $policy);

        $this->policyService->delete($policy);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Policy deleted.']);
        }

        return redirect()
            ->route('governance.policies.index')
            ->with('success', "Policy {$policy->policy_number} deleted.");
    }

    public function submitForReview(Request $request, Policy $policy)
    {
        $this->authorize('review', $policy);

        $request->validate(['comment' => 'nullable|string|max:2000']);

        $policy = $this->policyService->submitForReview($policy, $request->comment);

        return response()->json([
            'data' => $policy,
            'message' => "Policy {$policy->policy_number} submitted for review.",
        ]);
    }

    public function publish(Request $request, Policy $policy)
    {
        $this->authorize('publish', $policy);

        $request->validate([
            'effective_date' => 'required|date',
            'method' => 'sometimes|in:auto,manual',
        ]);

        $policy = $this->policyService->publish(
            $policy,
            $request->effective_date,
            $request->method ?? 'manual'
        );

        return response()->json([
            'data' => $policy,
            'message' => "Policy {$policy->policy_number} published.",
        ]);
    }

    public function deprecate(Request $request, Policy $policy)
    {
        $request->validate(['reason' => 'required|string|max:2000']);

        $policy = $this->policyService->deprecate($policy, $request->reason);

        return response()->json([
            'data' => $policy,
            'message' => "Policy {$policy->policy_number} deprecated.",
        ]);
    }

    public function archive(Policy $policy)
    {
        $policy = $this->policyService->archive($policy);

        return response()->json([
            'data' => $policy,
            'message' => "Policy {$policy->policy_number} archived.",
        ]);
    }

    public function reactivate(Policy $policy)
    {
        $policy = $this->policyService->reactivate($policy);

        return response()->json([
            'data' => $policy,
            'message' => "Policy {$policy->policy_number} reactivated.",
        ]);
    }

    public function expire(Policy $policy)
    {
        $policy = $this->policyService->expire($policy);

        return response()->json([
            'data' => $policy,
            'message' => "Policy {$policy->policy_number} expired.",
        ]);
    }

    public function versions(Request $request, Policy $policy)
    {
        $versions = $this->versionService->getVersionHistory($policy);

        if ($request->expectsJson()) {
            return response()->json(['data' => $versions]);
        }

        return view('governance.policies.versions', compact('policy', 'versions'));
    }
}
