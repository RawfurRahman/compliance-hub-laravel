<?php

namespace App\Http\Controllers;

use App\Imports\PciGapAssessmentImport;
use App\Models\PciGapAssessment;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class PciGapAssessmentController extends Controller
{
    /**
     * Display the PCI DSS Gap Assessment report.
     */
    public function index(Project $project)
    {
        $this->authorize('view', $project);
        $this->ensurePciProject($project);

        $assessments = $project->pciGapAssessments()->orderBy('id')->get();

        // Calculate progress stats for controls (exclude section headers)
        $controls = $assessments->where('is_section_header', false);
        $totalControls = $controls->count();
        $stats = [
            'total' => $totalControls,
            'yes' => $controls->where('status', 'Yes')->count(),
            'no' => $controls->where('status', 'No')->count(),
            'na' => $controls->where('status', 'N/A')->count(),
            'pending' => $controls->where('status', 'Pending')->count(),
            'progress' => $totalControls > 0 ? round(($controls->where('status', 'Yes')->count() / $totalControls) * 100) : 0,
        ];

        return view('pci-gap.index', compact('project', 'assessments', 'stats'));
    }

    /**
     * Handle the Excel/CSV file upload and import.
     */
    public function import(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        $this->ensurePciProject($project);

        $request->validate([
            'assessment_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            // Delete old assessments for this project before importing new ones
            $project->pciGapAssessments()->delete();

            Excel::import(
                new PciGapAssessmentImport($project->id), 
                $request->file('assessment_file')
            );

            return back()->with('success', 'PCI DSS v4.0.1 Gap Assessment imported successfully.');

        } catch (\Exception $e) {
            Log::error('PCI DSS Gap Assessment Import Error: ' . $e->getMessage());
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    /**
     * AJAX endpoint to update a gap assessment row.
     */
    public function updateRow(Request $request, $id)
    {
        $assessment = PciGapAssessment::findOrFail($id);
        $this->authorize('update', $assessment->project);
        $this->ensurePciProject($assessment->project);

        $validated = $request->validate([
            'status' => 'required|in:Yes,No,N/A,Pending',
            'na_explanation' => 'nullable|string',
            'milestone_date' => 'nullable|date',
            'comments' => 'nullable|string',
        ]);

        // If status is not N/A, explanation should be null
        if ($validated['status'] !== 'N/A') {
            $validated['na_explanation'] = null;
        }

        $assessment->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Row updated successfully.',
            'data' => $assessment
        ]);
    }

    /**
     * Ensure project is a PCI DSS module type.
     */
    private function ensurePciProject(Project $project)
    {
        abort_unless($project->module_type === 'pci_dss', 404, 'PCI DSS gap assessments are only available for PCI DSS projects.');
    }
}
