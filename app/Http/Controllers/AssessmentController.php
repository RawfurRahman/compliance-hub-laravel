<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentFinding;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssessmentController extends Controller
{
    // =========================================================================
    // DASHBOARD — show assessment for a project
    // =========================================================================

    /**
     * GET /assessments/{project}
     */
    public function show(Project $project, Request $request)
    {
        $type = ucfirst(strtolower($request->query('type', 'gap')));
        if (!in_array($type, ['Gap', 'Final'])) {
            $type = 'Gap';
        }

        // Determine framework from project's module_type
        $framework = match ($project->module_type) {
            'iso_27001' => 'ISO 27001',
            'hitrust'   => 'HITRUST',
            default     => 'ISO 27001',
        };

        $assessment = $project->assessments()
            ->where('framework', $framework)
            ->where('assessment_type', $type)
            ->with('findings')
            ->latest()
            ->first();

        $stats     = $assessment ? $assessment->stats() : $this->emptyStats();
        $ganttJson = $assessment ? json_encode($assessment->ganttTasks()) : '[]';

        return view('assessments.dashboard', compact(
            'project', 'assessment', 'type', 'stats', 'ganttJson', 'framework'
        ));
    }

    // =========================================================================
    // CREATE / INITIALISE an assessment
    // =========================================================================

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'assessment_type' => 'required|in:Gap,Final',
            'framework'       => 'required|in:ISO 27001,HITRUST',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
        ]);

        $assessment = $project->assessments()->create($data);

        return redirect()
            ->route('assessments.show', [$project, 'type' => strtolower($data['assessment_type'])])
            ->with('success', $data['assessment_type'] . ' Assessment initialised.');
    }

    // =========================================================================
    // CLONE gap → final
    // =========================================================================

    public function clone(Request $request, Project $project)
    {
        $request->validate([
            'source_id'  => 'required|exists:assessments,id',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $source = Assessment::with('findings')->findOrFail($request->source_id);

        $final = $project->assessments()->create([
            'assessment_type' => 'Final',
            'framework'       => $source->framework,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'cloned_from_id'  => $source->id,
        ]);

        // Deep-copy all findings
        foreach ($source->findings as $f) {
            $final->findings()->create($f->only([
                'serial_no', 'status', 'observation_title', 'risk_rating',
                'current_state', 'gap_description', 'impact_risk',
                'recommendation', 'standard_reference', 'is_compliant',
            ]) + ['cloned_from_finding_id' => $f->id]);
        }

        return redirect()
            ->route('assessments.show', [$project, 'type' => 'final'])
            ->with('success', 'Gap Assessment cloned to Final Assessment successfully.');
    }

    // =========================================================================
    // FINDINGS — CRUD
    // =========================================================================

    public function storeFinding(Request $request, Assessment $assessment)
    {
        $data = $request->validate([
            'serial_no'          => 'required|string|max:50',
            'status'             => 'required|in:Open,In Progress,Closed',
            'observation_title'  => 'required|string|max:255',
            'risk_rating'        => 'required|in:High,Medium,Low,None',
            'current_state'      => 'nullable|string',
            'gap_description'    => 'nullable|string',
            'impact_risk'        => 'nullable|string',
            'recommendation'     => 'nullable|string',
            'standard_reference' => 'nullable|string',
            'is_compliant'       => 'sometimes|boolean',
        ]);

        $data['is_compliant'] = $request->has('is_compliant') || $request->input('is_compliant') == 1;

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
            'status'             => 'sometimes|in:Open,In Progress,Closed',
            'observation_title'  => 'sometimes|string|max:255',
            'risk_rating'        => 'sometimes|in:High,Medium,Low,None',
            'current_state'      => 'nullable|string',
            'gap_description'    => 'nullable|string',
            'impact_risk'        => 'nullable|string',
            'recommendation'     => 'nullable|string',
            'standard_reference' => 'nullable|string',
            'is_compliant'       => 'sometimes|boolean',
        ]);

        if ($request->has('is_compliant')) {
            $data['is_compliant'] = $request->input('is_compliant') == 1;
        }

        $finding->update($data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'finding' => $finding->fresh()]);
        }

        return back()->with('success', 'Finding updated.');
    }

    public function destroyFinding(AssessmentFinding $finding)
    {
        $finding->delete();
        return response()->json(['success' => true]);
    }

    // =========================================================================
    // REPORT GENERATION
    // =========================================================================

    public function report(Assessment $assessment)
    {
        $assessment->load('findings', 'project');
        $project      = $assessment->project;
        $stats        = $assessment->stats();
        $highFindings = $assessment->findings->where('risk_rating', 'High');
        $type         = $assessment->assessment_type; // 'Gap' or 'Final'

        $pdf = Pdf::loadView('assessments.report', compact(
            'assessment', 'project', 'stats', 'highFindings', 'type'
        ))->setPaper('a4', 'portrait');

        $filename = ($assessment->framework === 'ISO 27001' ? 'ISO-27001' : 'HITRUST') . '-' . $type . '-Assessment-Report-' . $project->id . '.pdf';

        return $pdf->download($filename);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function emptyStats(): array
    {
        return [
            'total' => 0, 'compliant' => 0, 'nonCompliant' => 0,
            'high' => 0, 'medium' => 0, 'low' => 0, 'none' => 0,
            'open' => 0, 'inProgress' => 0, 'closed' => 0,
            'progressScore' => 0, 'compliancePct' => 0,
        ];
    }
}
