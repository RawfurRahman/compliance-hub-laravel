<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Framework;
use App\Models\ProjectAssessment;
use App\Models\AssessmentFinding;
use App\Models\Evidence;
use App\Services\AssessmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UnifiedAssessmentController extends Controller
{
    protected $assessmentService;

    public function __construct(AssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    /**
     * Show the unified dashboard.
     */
    public function show(Project $project, $framework_slug, $type)
    {
        $framework = Framework::where('slug', $framework_slug)
            ->where('is_active', true)
            ->firstOrFail();

        $type = ucfirst(strtolower($type));
        if (!in_array($type, ['Gap', 'Final'])) {
            abort(404);
        }

        // Enforce phase-dependency: Final Assessment (Phase 2) requires a 100% compliant Gap Assessment (Phase 1)
        if ($type === 'Final') {
            $gapAssessment = ProjectAssessment::where('project_id', $project->id)
                ->where('framework_id', $framework->id)
                ->where('type', 'Gap')
                ->first();

            if (!$gapAssessment || $gapAssessment->stats()['compliancePct'] < 100) {
                return redirect()
                    ->route('assessments.unified.show', [$project, $framework->slug, 'gap'])
                    ->with('error', 'Cannot start or access the Final Assessment (Phase 2) until the Gap Assessment (Phase 1) is 100% compliant.');
            }
        }

        $assessment = ProjectAssessment::where('project_id', $project->id)
            ->where('framework_id', $framework->id)
            ->where('type', $type)
            ->with(['findings.frameworkControl', 'findings.evidence'])
            ->first();

        if (!$assessment) {
            // Return view with no assessment so setup screen is displayed
            return view('assessments.unified-dashboard', [
                'project'    => $project,
                'framework'  => $framework,
                'type'       => $type,
                'assessment' => null,
                'stats'      => $this->emptyStats(),
                'ganttJson'  => '[]',
            ]);
        }

        $stats = $assessment->stats();
        $ganttJson = json_encode($assessment->ganttTasks());

        // Get all available evidence files for this project to populate the linking dropdown
        $projectEvidence = Evidence::where('project_id', $project->id)->latest()->get();

        return view('assessments.unified-dashboard', [
            'project'         => $project,
            'framework'       => $framework,
            'type'            => $type,
            'assessment'      => $assessment,
            'stats'           => $stats,
            'ganttJson'       => $ganttJson,
            'projectEvidence' => $projectEvidence,
        ]);
    }

    /**
     * Initialize a project assessment.
     */
    public function initialize(Request $request, Project $project, $framework_slug, $type)
    {
        $framework = Framework::where('slug', $framework_slug)->firstOrFail();
        $type = ucfirst(strtolower($type));

        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        // Enforce phase-dependency: Final Assessment (Phase 2) requires a 100% compliant Gap Assessment (Phase 1)
        if ($type === 'Final') {
            $gapAssessment = ProjectAssessment::where('project_id', $project->id)
                ->where('framework_id', $framework->id)
                ->where('type', 'Gap')
                ->first();

            if (!$gapAssessment || $gapAssessment->stats()['compliancePct'] < 100) {
                return redirect()->back()->with('error', 'Cannot start Final Assessment (Phase 2) until the Gap Assessment (Phase 1) is 100% compliant.');
            }
        }

        // Find or create ProjectAssessment
        $assessment = ProjectAssessment::firstOrCreate([
            'project_id'   => $project->id,
            'framework_id' => $framework->id,
            'type'         => $type,
        ], [
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'overall_status' => 'In Progress',
        ]);

        // Initialize findings
        $this->assessmentService->initialize($assessment);

        return redirect()
            ->route('assessments.unified.show', [$project, $framework->slug, strtolower($type)])
            ->with('success', "{$framework->name} {$type} Assessment initialized successfully.");
    }

    /**
     * Update an assessment finding.
     */
    public function updateFinding(Request $request, AssessmentFinding $finding)
    {
        $request->validate([
            'status'          => 'sometimes|in:Open,In Progress,Closed',
            'risk_rating'     => 'sometimes|in:High,Medium,Low,None',
            'is_compliant'    => 'sometimes|boolean',
            'observation'     => 'nullable|string',
            'gap_description' => 'nullable|string',
            'impact'          => 'nullable|string',
            'recommendation'  => 'nullable|string',
        ]);

        $data = $request->only([
            'status',
            'risk_rating',
            'is_compliant',
            'observation',
            'gap_description',
            'impact',
            'recommendation'
        ]);

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

        return back()->with('success', 'Finding updated successfully.');
    }

    /**
     * Upload a new evidence file and attach to a finding.
     */
    public function uploadEvidence(Request $request, AssessmentFinding $finding)
    {
        $request->validate([
            'file'        => 'required|file|max:20480', // 20MB Max
            'description' => 'nullable|string|max:500',
        ]);

        $project = $finding->projectAssessment->project;
        $file = $request->file('file');
        
        // Store the file in public storage
        $path = $file->store("evidence/{$project->id}", 'public');

        $evidence = Evidence::create([
            'project_id'     => $project->id,
            'requirement_id' => $finding->frameworkControl->id, // reference framework control id
            'name'           => $file->getClientOriginalName(),
            'path'           => $path,
            'url'            => Storage::url($path),
            'description'    => $request->description,
        ]);

        // Attach to finding
        $finding->evidence()->attach($evidence->id);

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'evidence' => $evidence,
            ]);
        }

        return back()->with('success', 'Evidence uploaded and linked successfully.');
    }

    /**
     * Attach an existing evidence file to a finding.
     */
    public function attachEvidence(Request $request, AssessmentFinding $finding)
    {
        $request->validate([
            'evidence_id' => 'required|exists:evidence,id',
        ]);

        // Avoid duplicate pivot entries
        if (!$finding->evidence()->where('evidence_id', $request->evidence_id)->exists()) {
            $finding->evidence()->attach($request->evidence_id);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'evidence' => $finding->evidence()->get()
            ]);
        }

        return back()->with('success', 'Evidence linked successfully.');
    }

    /**
     * Detach an evidence file from a finding.
     */
    public function detachEvidence(Request $request, AssessmentFinding $finding, Evidence $evidence)
    {
        $finding->evidence()->detach($evidence->id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
            ]);
        }

        return back()->with('success', 'Evidence unlinked successfully.');
    }

    /**
     * Export the assessment report as PDF.
     */
    public function report(ProjectAssessment $assessment)
    {
        $assessment->load(['findings.frameworkControl', 'findings.evidence', 'project', 'framework']);
        
        $project = $assessment->project;
        $framework = $assessment->framework;
        $stats = $assessment->stats();
        $findings = $assessment->findings;

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

    /**
     * Helper to return empty stats structure.
     */
    private function emptyStats(): array
    {
        return [
            'total'         => 0,
            'compliant'     => 0,
            'nonCompliant'  => 0,
            'high'          => 0,
            'medium'        => 0,
            'low'           => 0,
            'none'          => 0,
            'open'          => 0,
            'inProgress'    => 0,
            'closed'        => 0,
            'progressScore' => 0,
            'compliancePct' => 0,
        ];
    }
}
