<?php

namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Models\SLARule;
use App\Modules\Governance\Requests\StoreSLARuleRequest;
use App\Modules\Governance\Services\SLARuleService;
use Illuminate\Http\Request;

class SLARuleController extends Controller
{
    public function __construct(
        private SLARuleService $slaRuleService,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['policy_id', 'trigger_event', 'is_active']);
        $rules = $this->slaRuleService->list($filters);

        return response()->json(['data' => $rules]);
    }

    public function store(StoreSLARuleRequest $request)
    {
        $rule = $this->slaRuleService->create($request->validated());

        return response()->json(['data' => $rule, 'message' => 'SLA rule created.'], 201);
    }

    public function update(Request $request, SLARule $rule)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'trigger_event' => 'sometimes|string|max:50',
            'action_type' => 'sometimes|string|max:50',
            'sla_hours' => 'sometimes|integer|min:1',
            'escalation_interval_hours' => 'nullable|integer|min:1',
            'escalation_user_id' => 'nullable|exists:users,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $rule = $this->slaRuleService->update($rule, $data);

        return response()->json(['data' => $rule, 'message' => 'SLA rule updated.']);
    }

    public function destroy(SLARule $rule)
    {
        $this->slaRuleService->delete($rule);

        return response()->json(['message' => 'SLA rule deleted.']);
    }
}
