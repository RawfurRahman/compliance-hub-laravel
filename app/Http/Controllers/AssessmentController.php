<?php

namespace App\Http\Controllers;

use App\Models\AssessmentFinding;
use App\Models\Project;
use App\Models\ProjectAssessment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssessmentController extends Controller
{
    // =========================================================================
    // DASHBOARD — show assessment for a project (with mode-select modal)
    // =========================================================================

    /**
     * GET /assessments/{project}
     * Renders the mode-select modal if no type is chosen yet,
     * otherwise loads the correct assessment dashboard.
     */
    public function show(Project $project, Request $request)
    {
        $type = $request->query('type'); // 'gap' or 'final'

        if (!in_array($type, ['gap', 'final'])) {
            // Show the mode-selection landing page
            return view('assessments.select-mode', compact('project'));
        }

        $assessment = $project->assessments()
            ->where('type', $type)
            ->with('findings')
            ->latest()
            ->first();

        $stats     = $assessment ? $assessment->stats() : $this->emptyStats();
        $ganttJson = $assessment ? json_encode($assessment->ganttTasks()) : '[]';

        return view('assessments.dashboard', compact(
            'project', 'assessment', 'type', 'stats', 'ganttJson'
        ));
    }

    // =========================================================================
    // CREATE / INITIALISE an assessment
    // =========================================================================

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'type'       => 'required|in:gap,final',
            'framework'  => 'required|in:iso_27001,hitrust',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $assessment = $project->assessments()->create($data);

        return redirect()
            ->route('assessments.show', [$project, 'type' => $data['type']])
            ->with('success', ucfirst($data['type']) . ' Assessment initialised.');
    }

    // =========================================================================
    // CLONE gap → final
    // =========================================================================

    public function clone(Request $request, Project $project)
    {
        $request->validate([
            'source_id'  => 'required|exists:project_assessments,id',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $source = ProjectAssessment::with('findings')->findOrFail($request->source_id);

        $final = $project->assessments()->create([
            'type'          => 'final',
            'framework'     => $source->framework,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'cloned_from_id'=> $source->id,
        ]);

        // Deep-copy all findings
        foreach ($source->findings as $f) {
            $final->findings()->create($f->only([
                'serial_no', 'clause_reference', 'observation_title',
                'compliance_status', 'risk_rating', 'current_state',
                'gap_description', 'impact_risk', 'recommendation', 'status',
            ]));
        }

        return redirect()
            ->route('assessments.show', [$project, 'type' => 'final'])
            ->with('success', 'Gap Assessment cloned to Final Assessment successfully.');
    }

    // =========================================================================
    // FINDINGS — CRUD
    // =========================================================================

    public function storeFinding(Request $request, ProjectAssessment $assessment)
    {
        $data = $request->validate([
            'serial_no'          => 'required|string|max:50',
            'clause_reference'   => 'required|string|max:255',
            'observation_title'  => 'required|string|max:255',
            'compliance_status'  => 'required|in:Compliant,Partially Compliant,Non-Compliant,Not Applicable',
            'risk_rating'        => 'required|in:High,Medium,Low,None',
            'current_state'      => 'nullable|string',
            'gap_description'    => 'nullable|string',
            'impact_risk'        => 'nullable|string',
            'recommendation'     => 'nullable|string',
            'status'             => 'required|in:Open,In Progress,Closed',
        ]);

        $finding = $assessment->findings()->create($data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'finding' => $finding]);
        }

        return back()->with('success', 'Finding added.');
    }

    public function updateFinding(Request $request, AssessmentFinding $finding)
    {
        $data = $request->validate([
            'serial_no'          => 'sometimes|string|max:50',
            'clause_reference'   => 'sometimes|string|max:255',
            'observation_title'  => 'sometimes|string|max:255',
            'compliance_status'  => 'sometimes|in:Compliant,Partially Compliant,Non-Compliant,Not Applicable',
            'risk_rating'        => 'sometimes|in:High,Medium,Low,None',
            'current_state'      => 'nullable|string',
            'gap_description'    => 'nullable|string',
            'impact_risk'        => 'nullable|string',
            'recommendation'     => 'nullable|string',
            'status'             => 'sometimes|in:Open,In Progress,Closed',
        ]);

        $finding->update($data);

        return response()->json(['success' => true, 'finding' => $finding->fresh()]);
    }

    public function destroyFinding(AssessmentFinding $finding)
    {
        $finding->delete();
        return response()->json(['success' => true]);
    }

    // =========================================================================
    // REPORT GENERATION
    // =========================================================================

    public function report(ProjectAssessment $assessment)
    {
        $assessment->load('findings', 'project');
        $project      = $assessment->project;
        $stats        = $assessment->stats();
        $highFindings = $assessment->findings->where('risk_rating', 'High');
        $type         = $assessment->type;

        $pdf = Pdf::loadView('assessments.report', compact(
            'assessment', 'project', 'stats', 'highFindings', 'type'
        ))->setPaper('a4', 'portrait');

        $filename = 'ISO-27001-' . Str::title($type) . '-Assessment-Report-' . $project->id . '.pdf';

        return $pdf->download($filename);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function emptyStats(): array
    {
        return [
            'total' => 0, 'compliant' => 0, 'partial' => 0,
            'nonCompliant' => 0, 'na' => 0,
            'high' => 0, 'medium' => 0, 'low' => 0,
            'open' => 0, 'inProgress' => 0, 'closed' => 0,
            'progressScore' => 0, 'compliancePct' => 0,
        ];
    }
}
