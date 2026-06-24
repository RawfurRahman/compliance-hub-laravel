<?php

namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyWaiver;
use App\Modules\Governance\Requests\StoreWaiverRequest;
use App\Modules\Governance\Services\PolicyWaiverService;
use Illuminate\Http\Request;

class PolicyWaiverController extends Controller
{
    public function __construct(
        private PolicyWaiverService $waiverService,
    ) {}

    public function index(Policy $policy)
    {
        $waivers = $this->waiverService->listForPolicy($policy);

        return response()->json(['data' => $waivers]);
    }

    public function store(StoreWaiverRequest $request)
    {
        $waiver = $this->waiverService->request($request->validated());

        return response()->json(['data' => $waiver, 'message' => 'Waiver requested.'], 201);
    }

    public function approve(Request $request, PolicyWaiver $waiver)
    {
        $this->authorize('waive');

        $request->validate(['approved_by' => 'required|exists:users,id']);

        $waiver = $this->waiverService->approve($waiver, $request->approved_by);

        return response()->json(['data' => $waiver, 'message' => 'Waiver approved.']);
    }

    public function reject(Request $request, PolicyWaiver $waiver)
    {
        $this->authorize('waive');

        $request->validate(['reason' => 'required|string|max:2000']);

        $waiver = $this->waiverService->reject($waiver, $request->reason);

        return response()->json(['data' => $waiver, 'message' => 'Waiver rejected.']);
    }

    public function revoke(PolicyWaiver $waiver)
    {
        $waiver = $this->waiverService->revoke($waiver);

        return response()->json(['data' => $waiver, 'message' => 'Waiver revoked.']);
    }
}
