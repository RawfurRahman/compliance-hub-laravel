<?php

namespace App\Http\Controllers;

use App\Models\AssessmentFinding;
use App\Models\Observation;
use App\Models\Project;
use App\Services\ObservationService;
use Illuminate\Http\Request;

class ObservationController extends Controller
{
    private ObservationService $observationService;

    public function __construct(ObservationService $observationService)
    {
        $this->observationService = $observationService;
    }

    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $observations = Observation::where('project_id', $project->id)
            ->with(['finding.frameworkControl', 'owner', 'raisedBy'])
            ->latest()
            ->get();

        return response()->json(['observations' => $observations]);
    }

    public function store(Request $request, AssessmentFinding $finding)
    {
        $project = $finding->projectAssessment->project ?? abort(404);
        $this->authorize('update', $project);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'gap' => 'nullable|string|max:5000',
            'risk_impact' => 'nullable|string|max:5000',
            'recommendation' => 'nullable|string|max:5000',
            'owner_user_id' => 'nullable|exists:users,id',
            'target_date' => 'nullable|date',
        ]);

        try {
            $observation = $this->observationService->createFromFinding($finding, $validated);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'observation' => $observation], 201);
    }

    public function update(Request $request, Observation $observation)
    {
        $this->authorize('update', $observation->project);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string|max:5000',
            'gap' => 'sometimes|nullable|string|max:5000',
            'risk_impact' => 'sometimes|nullable|string|max:5000',
            'recommendation' => 'sometimes|nullable|string|max:5000',
            'management_response' => 'sometimes|nullable|string|max:5000',
            'corrective_action' => 'sometimes|nullable|string|max:5000',
            'owner_user_id' => 'sometimes|nullable|exists:users,id',
            'target_date' => 'sometimes|nullable|date',
            'status' => 'sometimes|in:'.implode(',', Observation::STATUSES),
        ]);

        try {
            $observation = $this->observationService->update($observation, $validated);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'observation' => $observation]);
    }

    public function sendToFinalAssessment(Observation $observation)
    {
        $this->authorize('update', $observation->project);

        try {
            $observation = $this->observationService->sendToFinalAssessment($observation);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'observation' => $observation]);
    }

    public function addToRiskRegister(Observation $observation)
    {
        $this->authorize('update', $observation->project);

        try {
            $risk = $this->observationService->addToRiskRegister($observation);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'risk' => $risk, 'observation' => $observation->fresh()]);
    }
}
