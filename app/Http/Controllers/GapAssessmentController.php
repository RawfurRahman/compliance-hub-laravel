<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Department;
use App\Models\GapControl;
use App\Models\EvidenceFile;
use App\Imports\GapAssessmentImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class GapAssessmentController extends Controller
{
    /**
     * Display the Gap Assessment Dashboard for a specific project.
     */
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        // Eager load gap controls grouped by department
        $departments = Department::with(['gapControls' => function($q) use ($project) {
            $q->where('project_id', $project->id)->with('evidenceFiles');
        }])->get()->filter(function($dept) {
            return $dept->gapControls->count() > 0;
        })->values();

        $allDepartments = Department::all();

        // Calculate progress metrics
        $totalControls = $project->gapControls()->count();
        $completedControls = $project->gapControls()->where('status', 'Done')->count();
        $projectProgress = $totalControls > 0 ? round(($completedControls / $totalControls) * 100) : 0;

        return view('gap-assessment.index', compact('project', 'departments', 'allDepartments', 'projectProgress', 'totalControls', 'completedControls'));
    }

    /**
     * Update the status of a specific gap control.
     */
    public function updateStatus(Request $request, Project $project, GapControl $control)
    {
        $this->authorize('update', $project);

        $request->validate([
            'status' => 'required|in:Pending,Done',
        ]);

        $control->update([
            'status' => $request->status,
        ]);

        // Recalculate progress metrics
        $totalControls = $project->gapControls()->count();
        $completedControls = $project->gapControls()->where('status', 'Done')->count();
        $projectProgress = $totalControls > 0 ? round(($completedControls / $totalControls) * 100) : 0;

        // Recalculate department progress
        $deptControls = $project->gapControls()->where('department_id', $control->department_id)->count();
        $deptCompleted = $project->gapControls()->where('department_id', $control->department_id)->where('status', 'Done')->count();
        $deptProgress = $deptControls > 0 ? round(($deptCompleted / $deptControls) * 100) : 0;

        return response()->json([
            'status' => 'success',
            'control_status' => $control->status,
            'project_progress' => $projectProgress,
            'completed_controls' => $completedControls,
            'department_progress' => $deptProgress,
        ]);
    }

    /**
     * Handle the automated Excel import process.
     */
    public function importExcel(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file');

        try {
            Excel::import(new GapAssessmentImport($project->id, $file->getRealPath()), $file);
            return back()->with('success', 'Excel assessment imported and controls populated successfully!');
        } catch (\Exception $e) {
            Log::error('Gap assessment import failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to import assessment: ' . $e->getMessage());
        }
    }

    /**
     * Upload and attach evidence to a specific gap control.
     */
    public function attachEvidence(Request $request, Project $project, GapControl $control)
    {
        $this->authorize('update', $project);

        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $path = $file->store("evidence/{$project->id}", 'public');

        // Eager load first PCI requirement to satisfy database constraint
        $firstRequirementId = \App\Models\PciDssRequirement::value('id');
        if (!$firstRequirementId) {
            return back()->with('error', 'Please seed PCI DSS requirements first.');
        }

        $evidence = $project->evidenceFiles()->create([
            'pci_dss_requirement_id' => $firstRequirementId,
            'user_id' => auth()->id(),
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'scan_status' => 'pending',
            'ai_analysis_status' => 'pending',
        ]);

        // Attach to gap control
        $control->evidenceFiles()->attach($evidence->id);

        // Trigger n8n security scan
        $n8nScanWebhookUrl = env('N8N_FILE_SCAN_WEBHOOK_URL', 'http://localhost:5678/webhook/file-scan');
        if ($n8nScanWebhookUrl) {
            try {
                Http::timeout(1)->retry(0)
                    ->attach(
                        'file',
                        Storage::disk('public')->get($path),
                        $evidence->original_filename
                    )
                    ->attach('evidence_file_id', $evidence->id)
                    ->attach('project_id', $project->id)
                    ->post($n8nScanWebhookUrl);
            } catch (\Exception $e) {
                // Ignore expected timeout
            }
        }

        return back()->with('success', 'Evidence uploaded and attached to control ' . $control->control_id);
    }
}
