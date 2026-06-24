<?php

namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyApproval;
use App\Modules\Governance\Services\PolicyApprovalService;
use Illuminate\Http\Request;

class PolicyApprovalController extends Controller
{
    public function __construct(
        private PolicyApprovalService $approvalService,
    ) {}

    public function index(Policy $policy)
    {
        $approvals = $this->approvalService->listForPolicy($policy);

        return response()->json(['data' => $approvals]);
    }

    public function store(Request $request, Policy $policy)
    {
        $this->authorize('approve', $policy);

        $request->validate([
            'approver_user_id' => 'required|exists:users,id',
            'approval_type' => 'sometimes|in:initial,re_approval,emergency',
        ]);

        $approval = $this->approvalService->requestApproval(
            $policy,
            $request->approver_user_id,
            $request->approval_type ?? 'initial'
        );

        return response()->json(['data' => $approval, 'message' => 'Approval requested.'], 201);
    }

    public function approve(Request $request, Policy $policy, PolicyApproval $approval)
    {
        if ($approval->policy_id !== $policy->id) {
            abort(404);
        }

        $this->authorize('approve', $policy);

        $request->validate(['comments' => 'nullable|string|max:2000']);

        $approval = $this->approvalService->approve($approval, $request->comments);

        return response()->json(['data' => $approval, 'message' => 'Policy approved.']);
    }

    public function reject(Request $request, Policy $policy, PolicyApproval $approval)
    {
        if ($approval->policy_id !== $policy->id) {
            abort(404);
        }

        $request->validate(['reason' => 'required|string|max:2000']);

        $approval = $this->approvalService->reject($approval, $request->reason);

        return response()->json(['data' => $approval, 'message' => 'Policy rejected.']);
    }
}
