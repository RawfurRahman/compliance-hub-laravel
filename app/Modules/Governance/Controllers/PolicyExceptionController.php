<?php

namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyException;
use App\Modules\Governance\Requests\StoreExceptionRequest;
use App\Modules\Governance\Services\PolicyExceptionService;
use Illuminate\Http\Request;

class PolicyExceptionController extends Controller
{
    public function __construct(
        private PolicyExceptionService $exceptionService,
    ) {}

    public function index(Policy $policy)
    {
        $exceptions = $this->exceptionService->listForPolicy($policy);

        return response()->json(['data' => $exceptions]);
    }

    public function store(StoreExceptionRequest $request)
    {
        $exception = $this->exceptionService->request($request->validated());

        return response()->json(['data' => $exception, 'message' => 'Exception requested.'], 201);
    }

    public function approve(Request $request, PolicyException $exception)
    {
        $this->authorize('waive');

        $request->validate(['approved_by' => 'required|exists:users,id']);

        $exception = $this->exceptionService->approve($exception, $request->approved_by);

        return response()->json(['data' => $exception, 'message' => 'Exception approved.']);
    }

    public function reject(Request $request, PolicyException $exception)
    {
        $this->authorize('waive');

        $request->validate(['reason' => 'required|string|max:2000']);

        $exception = $this->exceptionService->reject($exception, $request->reason);

        return response()->json(['data' => $exception, 'message' => 'Exception rejected.']);
    }

    public function revoke(PolicyException $exception)
    {
        $exception = $this->exceptionService->revoke($exception);

        return response()->json(['data' => $exception, 'message' => 'Exception revoked.']);
    }
}
