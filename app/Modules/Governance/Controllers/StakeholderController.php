<?php

namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\Stakeholder;
use App\Modules\Governance\Requests\StoreStakeholderRequest;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StakeholderController extends Controller
{
    public function index(Policy $policy)
    {
        return response()->json(['data' => $policy->stakeholders()->with('user')->get()]);
    }

    public function store(StoreStakeholderRequest $request, Policy $policy)
    {
        $stakeholder = $policy->stakeholders()->create($request->validated());

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'stakeholder_added',
            'description' => "Stakeholder added to policy {$policy->policy_number}",
            'details' => [
                'policy_id' => $policy->id,
                'stakeholder_id' => $stakeholder->id,
                'type' => $request->stakeholder_type,
            ],
            'ip_address' => request()->ip(),
        ]);

        return response()->json(['data' => $stakeholder->load('user'), 'message' => 'Stakeholder added.'], 201);
    }

    public function destroy(Policy $policy, Stakeholder $stakeholder)
    {
        if ($stakeholder->policy_id !== $policy->id) {
            abort(404);
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'stakeholder_removed',
            'description' => "Stakeholder removed from policy {$policy->policy_number}",
            'details' => [
                'policy_id' => $policy->id,
                'stakeholder_id' => $stakeholder->id,
            ],
            'ip_address' => request()->ip(),
        ]);

        $stakeholder->delete();

        return response()->json(['message' => 'Stakeholder removed.']);
    }
}
