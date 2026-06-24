<?php

namespace App\Modules\Compliance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Control;
use App\Models\Project;
use App\Modules\Compliance\Services\ControlTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ControlTestController extends Controller
{
    public function __construct(
        private ControlTestService $service,
    ) {}

    public function index(Request $request, Project $project)
    {
        $failed = $this->service->getFailedTests($project->id);
        return view('compliance.control-tests', compact('project', 'failed'));
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'control_id' => 'required|exists:controls,id',
            'test_type' => 'required|string|max:50',
            'result' => 'required|in:pass,fail,partial,error,inconclusive',
            'score' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'evidence_summary' => 'nullable|string',
            'framework_version_id' => 'nullable|exists:comp_framework_versions,id',
            'assessment_finding_id' => 'nullable|exists:assessment_findings,id',
        ]);

        $test = $this->service->execute(
            controlId: $data['control_id'],
            testedBy: Auth::id(),
            testType: $data['test_type'],
            result: $data['result'],
            score: $data['score'],
            notes: $data['notes'],
            evidenceSummary: $data['evidence_summary'] ?? null,
            frameworkVersionId: $data['framework_version_id'] ?? null,
            findingId: $data['assessment_finding_id'] ?? null,
        );

        return response()->json($test->load('control', 'testedBy', 'assessmentFinding'), 201);
    }

    public function show(Project $project, int $testId)
    {
        $test = \App\Modules\Compliance\Models\ControlTest::with('control', 'testedBy', 'assessmentFinding')->findOrFail($testId);
        return view('compliance.control-test-show', compact('project', 'test'));
    }

    public function history(Project $project, int $controlId): JsonResponse
    {
        $history = $this->service->getHistory($controlId);
        return response()->json($history);
    }
}
