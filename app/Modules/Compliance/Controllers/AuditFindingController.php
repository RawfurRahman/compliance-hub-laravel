<?php

namespace App\Modules\Compliance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\Compliance\Services\AuditFindingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditFindingController extends Controller
{
    public function __construct(
        private AuditFindingService $service,
    ) {}

    public function index(Project $project)
    {
        $findings = $this->service->getByProject($project->id);
        return view('compliance.audit-findings', compact('project', 'findings'));
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'finding_reference' => 'required|string|unique:comp_audit_findings,finding_reference',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'audit_date' => 'required|date',
            'auditor_id' => 'nullable|exists:users,id',
            'severity' => 'required|in:critical,high,medium,low',
            'status' => 'required|in:open,in_review,resolved,closed',
            'control_id' => 'nullable|exists:controls,id',
            'framework_control_id' => 'nullable|exists:framework_controls,id',
            'remediation_plan' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $data['project_id'] = $project->id;
        $finding = $this->service->create($data);

        return response()->json($finding, 201);
    }

    public function show(Project $project, int $findingId)
    {
        $finding = \App\Modules\Compliance\Models\AuditFinding::with('auditor', 'control')->findOrFail($findingId);
        return view('compliance.audit-finding-show', compact('project', 'finding'));
    }

    public function update(Request $request, Project $project, int $findingId): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'severity' => 'sometimes|in:critical,high,medium,low',
            'status' => 'sometimes|in:open,in_review,resolved,closed',
            'remediation_plan' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $finding = \App\Modules\Compliance\Models\AuditFinding::findOrFail($findingId);
        $finding->update($data);

        return response()->json($finding);
    }

    public function close(Request $request, Project $project, int $findingId): JsonResponse
    {
        $data = $request->validate(['resolution' => 'nullable|string']);
        $finding = $this->service->close($findingId, $data['resolution'] ?? null);
        return response()->json(['message' => 'Audit finding closed', 'finding' => $finding]);
    }
}
