<?php

namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Requests\StoreOwnershipRequest;
use App\Modules\Governance\Services\OwnershipService;
use Illuminate\Http\Request;

class OwnershipController extends Controller
{
    public function __construct(
        private OwnershipService $ownershipService,
    ) {}

    public function index(Policy $policy)
    {
        $matrix = $this->ownershipService->getMatrixForPolicy($policy);

        return response()->json(['data' => $matrix]);
    }

    public function store(StoreOwnershipRequest $request, Policy $policy)
    {
        $assignment = $this->ownershipService->assignOwner($policy, $request->validated());

        return response()->json(['data' => $assignment, 'message' => 'Owner assigned.'], 201);
    }

    public function destroy(Policy $policy, int $id)
    {
        $this->ownershipService->removeAssignment($id);

        return response()->json(['message' => 'Assignment removed.']);
    }
}
