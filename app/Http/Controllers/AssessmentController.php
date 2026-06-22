<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Evidence;
use App\Services\AssessmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssessmentController extends Controller
{
    protected $assessmentService;

    public function __construct(AssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

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

        // Determine framework from project's module_type (slug)
        $frameworkModel = Framework::where('slug', $project->module_type)
            ->where('is_active', true)
            ->firstOrFail();

        // Enforce phase-dependency: Final Assessment (Phase 2) requires a 100% compliant Gap Assessment (Phase 1)
        if ($type === 'Final') {
            $gapAssessment = ProjectAssessment::where('project_id', $project->id)
                ->where('framework_id', $frameworkModel->id)
                ->where('type', 'Gap')
                ->first();

            if (!$gapAssessment || $gapAssessment->stats()['compliancePct'] < 100) {
                return redirect()
                    ->route('assessments.show', [$project, 'type' => 'gap'])
                    ->with('error', 'Cannot start or access the Final Assessment (Phase 2) until the Gap Assessment (Phase 1) is 100% compliant.');
            }
        }

        $assessment = ProjectAssessment::where('project_id', $project->id)
            ->where('framework_id', $frameworkModel->id)
            ->where('type', $type)
            ->with(['findings.frameworkControl', 'findings.evidence'])
            ->latest()
            ->first();

        $stats     = $assessment ? $assessment->stats() : $this->emptyStats();
        $ganttJson = $assessment ? json_encode($assessment->ganttTasks()) : '[]';

        // Get all available evidence files for this project to populate the linking dropdown
        $projectEvidence = Evidence::where('project_id', $project->id)->latest()->get();

        $framework = $frameworkModel->name;

        return view('assessments.dashboard', compact(
            'project', 'assessment', 'type', 'stats', 'ganttJson', 'framework', 'projectEvidence'
        ));
    }

    // =========================================================================
    // CREATE / INITIALISE an assessment
    // =========================================================================

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'assessment_type' => 'required|in:Gap,Final',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
        ]);

        $framework = Framework::where('slug', $project->module_type)->firstOrFail();

        // Enforce phase-dependency: Final Assessment (Phase 2) requires a 100% compliant Gap Assessment (Phase 1)
        if ($data['assessment_type'] === 'Final') {
            $gapAssessment = ProjectAssessment::where('project_id', $project->id)
                ->where('framework_id', $framework->id)
                ->where('type', 'Gap')
                ->first();

            if (!$gapAssessment || $gapAssessment->stats()['compliancePct'] < 100) {
                return redirect()->back()->with('error', 'Cannot start Final Assessment (Phase 2) until the Gap Assessment (Phase 1) is 100% compliant.');
            }
        }

        $assessment = ProjectAssessment::firstOrCreate([
            'project_id'   => $project->id,
            'framework_id' => $framework->id,
            'type'         => $data['assessment_type'],
        ], [
            'start_date'     => $data['start_date'],
            'end_date'       => $data['end_date'],
            'overall_status' => 'In Progress',
        ]);

        // Initialize findings for all controls
        $this->assessmentService->initialize($assessment);

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
            'source_id'  => 'required|exists:project_assessments,id',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $source = ProjectAssessment::with('findings')->findOrFail($request->source_id);

        // Enforce phase-dependency: Final Assessment (Phase 2) requires a 100% compliant Gap Assessment (Phase 1)
        if ($source->stats()['compliancePct'] < 100) {
            return redirect()->back()->with('error', 'Cannot clone to Final Assessment (Phase 2) until the Gap Assessment (Phase 1) is 100% compliant.');
        }

        $final = ProjectAssessment::firstOrCreate([
            'project_id'   => $project->id,
            'framework_id' => $source->framework_id,
            'type'         => 'Final',
        ], [
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'cloned_from_id' => $source->id,
            'overall_status' => 'In Progress',
        ]);

        // Deep-copy all findings
        foreach ($source->findings as $f) {
            $clonedFinding = AssessmentFinding::updateOrCreate(
                [
                    'project_assessment_id' => $final->id,
                    'framework_control_id'  => $f->framework_control_id,
                ],
                [
                    'status'                 => $f->status,
                    'risk_rating'            => $f->risk_rating,
                    'observation'            => $f->observation,
                    'gap_description'        => $f->gap_description,
                    'impact'                 => $f->impact,
                    'recommendation'         => $f->recommendation,
                    'is_compliant'           => $f->is_compliant,
                    'cloned_from_finding_id' => $f->id,
                ]
            );

            // Sync evidence pivot records
            $evidenceIds = $f->evidence()->pluck('evidence.id')->toArray();
            $clonedFinding->evidence()->sync($evidenceIds);
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
        $request->validate([
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

        // Resolve or create a FrameworkControl corresponding to serial_no (control_id)
        $control = FrameworkControl::firstOrCreate([
            'framework_id' => $assessment->framework_id,
            'control_id'   => $request->serial_no,
        ], [
            'domain'                  => 'General',
            'requirement_description' => $request->observation_title,
        ]);

        $finding = $assessment->findings()->create([
            'framework_control_id' => $control->id,
            'status'               => $request->status,
            'risk_rating'          => $request->risk_rating,
            'observation'          => $request->observation_title . ($request->current_state ? "\n\n" . $request->current_state : ""),
            'gap_description'      => $request->gap_description . ($request->standard_reference ? "\n\nStandard Reference: " . $request->standard_reference : ""),
            'impact'               => $request->impact_risk,
            'recommendation'       => $request->recommendation,
            'is_compliant'         => $request->has('is_compliant') || $request->input('is_compliant') == 1,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'finding' => $finding]);
        }

        return back()->with('success', 'Finding added.');
    }

    public function updateFinding(Request $request, AssessmentFinding $finding)
    {
        $request->validate([
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
            'observation'        => 'nullable|string',
            'impact'             => 'nullable|string',
        ]);

        $data = [];

        if ($request->has('serial_no')) {
            $control = FrameworkControl::firstOrCreate([
                'framework_id' => $finding->projectAssessment->framework_id,
                'control_id'   => $request->serial_no,
            ], [
                'domain'                  => 'General',
                'requirement_description' => $request->observation_title ?? '',
            ]);
            $data['framework_control_id'] = $control->id;
        }

        if ($request->has('status')) {
            $data['status'] = $request->status;
        }

        if ($request->has('risk_rating')) {
            $data['risk_rating'] = $request->risk_rating;
        }

        if ($request->has('observation')) {
            $data['observation'] = $request->observation;
        } elseif ($request->has('observation_title') || $request->has('current_state')) {
            $title = $request->observation_title ?? $finding->observation_title;
            $state = $request->current_state ?? '';
            $data['observation'] = $title . ($state ? "\n\n" . $state : "");
        }

        if ($request->has('gap_description')) {
            $data['gap_description'] = $request->gap_description;
        }

        if ($request->has('impact')) {
            $data['impact'] = $request->impact;
        } elseif ($request->has('impact_risk')) {
            $data['impact'] = $request->impact_risk;
        }

        if ($request->has('recommendation')) {
            $data['recommendation'] = $request->recommendation;
        }

        if ($request->has('is_compliant')) {
            $data['is_compliant'] = filter_var($request->is_compliant, FILTER_VALIDATE_BOOLEAN);
        }

        $finding->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'finding' => $finding->fresh(['frameworkControl', 'evidence']),
                'stats'   => $finding->projectAssessment->stats()
            ]);
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

    public function report(ProjectAssessment $assessment)
    {
        $assessment->load(['findings.frameworkControl', 'findings.evidence', 'project', 'framework']);
        
        $project   = $assessment->project;
        $framework = $assessment->framework;
        $stats     = $assessment->stats();
        $findings  = $assessment->findings;

        $pdf = Pdf::loadView('assessments.report-pdf', compact(
            'assessment', 'project', 'framework', 'stats', 'findings'
        ))->setPaper('a4', 'portrait');

        $filename = sprintf(
            '%s-%s-Assessment-Report-%s.pdf',
            Str::slug($framework->name),
            Str::slug($assessment->type),
            $project->id
        );

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
