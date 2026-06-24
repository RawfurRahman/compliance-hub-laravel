<?php

namespace App\Modules\Compliance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AssessmentFinding;
use App\Models\Project;
use App\Modules\Compliance\Services\RemediationService;
use App\Modules\RiskManagement\Models\RiskTreatmentPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RemediationController extends Controller
{
    public function __construct(
        private RemediationService $service,
    ) {}

    public function index(Request $request, Project $project)
    {
        $plans = $this->service->getOverdueBySLA($project->id);
        return view('compliance.remediations', compact('project', 'plans'));
    }

    public function show(Project $project, int $planId)
    {
        $plan = RiskTreatmentPlan::with('risk')->findOrFail($planId);
        return view('compliance.remediation-show', compact('project', 'plan'));
    }

    public function close(Request $request, Project $project, int $planId): JsonResponse
    {
        $data = $request->validate(['notes' => 'nullable|string']);
        $plan = RiskTreatmentPlan::findOrFail($planId);
        $this->service->closePlan($plan, $data['notes'] ?? null);
        return response()->json(['message' => 'Plan closed']);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'assessment_finding_id' => 'required|exists:assessment_findings,id',
        ]);

        $finding = AssessmentFinding::findOrFail($data['assessment_finding_id']);
        $plan = $this->service->createFromFinding($finding, Auth::id());

        return response()->json($plan, 201);
    }
}
