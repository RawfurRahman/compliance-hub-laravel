<?php

namespace App\Http\Controllers;

use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\Project;
use App\Services\GapAssessmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class UnifiedGapAssessmentController extends Controller
{
    protected GapAssessmentService $gapService;

    public function __construct(GapAssessmentService $gapService)
    {
        $this->gapService = $gapService;
    }

    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $framework = $this->resolveFramework($project);
        if (! $framework) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'No framework linked to this project.');
        }

        $assessment = $this->gapService->findOrCreateAssessment($project, $framework);
        $groupedFindings = $this->gapService->getGroupedFindings($assessment);
        $groupedStats = $this->gapService->getGroupedStats($groupedFindings);
        $overallStats = $assessment->stats();

        return view('gap-assessment.index', compact(
            'project',
            'assessment',
            'framework',
            'groupedFindings',
            'groupedStats',
            'overallStats'
        ));
    }

    public function initialize(Project $project, Framework $framework)
    {
        $this->authorize('update', $project);

        $assessment = $this->gapService->findOrCreateAssessment($project, $framework);
        $created = $this->gapService->initialize($assessment);

        return redirect()->route('projects.gap-assessment', $project)
            ->with('success', "Assessment initialized with {$created} controls.");
    }

    public function update(Request $request, Project $project, AssessmentFinding $finding)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'status' => 'sometimes|in:Open,In Progress,Closed',
            'risk_rating' => 'sometimes|in:None,Low,Medium,High',
            'is_compliant' => 'sometimes|boolean',
            'observation' => 'sometimes|nullable|string|max:5000',
            'gap_description' => 'sometimes|nullable|string|max:5000',
            'impact' => 'sometimes|nullable|string|max:5000',
            'recommendation' => 'sometimes|nullable|string|max:5000',
            'due_date' => 'sometimes|nullable|date',
            'is_applicable' => 'sometimes|boolean',
            'gap_category' => 'sometimes|string',
            'non_compliant_details' => 'sometimes|nullable|string|max:5000',
            'compliant_description' => 'sometimes|nullable|string|max:5000',
            'remediation_plan' => 'sometimes|nullable|string|max:5000',
            'evidence_provided' => 'sometimes|string',
            'test_results' => 'sometimes|string',
            'meets_standard' => 'sometimes|boolean',
            'auditor_notes' => 'sometimes|nullable|string|max:5000',
        ]);

        $finding = $this->gapService->updateFinding($finding, $validated);

        return response()->json([
            'success' => true,
            'finding' => $finding->load('frameworkControl'),
        ]);
    }

    public function getFinding(Project $project, AssessmentFinding $finding)
    {
        $this->authorize('view', $project);

        $finding->load('frameworkControl', 'evidence');
        $evidenceFiles = $this->gapService->getEvidenceFiles($finding);

        return response()->json([
            'success' => true,
            'finding' => $finding,
            'evidence_files' => $evidenceFiles,
        ]);
    }

    public function report(Project $project)
    {
        $this->authorize('view', $project);

        $framework = $this->resolveFramework($project);
        $assessment = $this->gapService->findOrCreateAssessment($project, $framework);
        $groupedFindings = $this->gapService->getGroupedFindings($assessment);
        $overallStats = $assessment->stats();

        $pdf = Pdf::loadView('gap-assessment.report', compact(
            'project', 'assessment', 'framework', 'groupedFindings', 'overallStats'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("Gap-Assessment-{$project->id}.pdf");
    }

    private function resolveFramework(Project $project): ?Framework
    {
        if ($project->module_type) {
            return Framework::where('slug', $project->module_type)->where('is_active', true)->first();
        }

        return Framework::where('is_active', true)->first();
    }
}
