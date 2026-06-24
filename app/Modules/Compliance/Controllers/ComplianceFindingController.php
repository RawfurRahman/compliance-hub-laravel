<?php

namespace App\Modules\Compliance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AssessmentFinding;
use App\Models\Project;
use App\Modules\Compliance\Services\ComplianceFindingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceFindingController extends Controller
{
    public function __construct(
        private ComplianceFindingService $service,
    ) {}

    public function index(Request $request, Project $project)
    {
        $state = $request->get('state');
        $findings = $this->service->getByProject($project->id, $state);
        return view('compliance.findings', compact('project', 'findings'));
    }

    public function updateState(Request $request, Project $project, int $findingId): JsonResponse
    {
        $data = $request->validate([
            'compliance_state' => 'required|in:compliant,partially_compliant,non_compliant,overdue,waived,under_review',
        ]);

        $finding = AssessmentFinding::findOrFail($findingId);
        $this->service->setState($finding, $data['compliance_state']);

        return response()->json($finding);
    }
}
