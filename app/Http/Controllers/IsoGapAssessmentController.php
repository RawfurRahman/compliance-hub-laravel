<?php

namespace App\Http\Controllers;

use App\Imports\IsoGapAssessmentImport;
use App\Models\EvidenceFile;
use App\Models\IsoGapAssessment;
use App\Models\Project;
use App\Services\UploadRejectionLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class IsoGapAssessmentController extends Controller
{
    /**
     * Display the gap assessment dashboard for a project.
     */
    public function index(int $project_id)
    {
        $project = Project::findOrFail($project_id);
        $findings = $project->isoGapAssessments()->orderBy('serial_no')->get();

        $stats = $this->buildStats($findings);

        return view('iso-gap.index', compact('project', 'findings', 'stats'));
    }

    /**
     * Handle Excel import for a project's gap assessment data.
     */
    public function import(Request $request, int $project_id)
    {
        $formats = implode(', ', config('uploads.imports.extensions'));

        UploadRejectionLogger::validate(
            $request,
            [
                'file' => [
                    'required',
                    'file',
                    'mimes:'.implode(',', config('uploads.imports.extensions')),
                    'mimetypes:'.implode(',', config('uploads.imports.mimetypes')),
                    'max:'.(int) config('uploads.imports.max_size_kb'),
                ],
            ],
            'data-import.rejected',
            [
                'file.mimes' => "The file must be one of the accepted import formats: {$formats}.",
                'file.mimetypes' => "The file content does not match the accepted import formats. Allowed: {$formats}.",
                'file.max' => 'The file may not be larger than '.((int) config('uploads.imports.max_size_kb') / 1024).' MB.',
            ],
        );

        Project::findOrFail($project_id); // ensure project exists

        Excel::import(new IsoGapAssessmentImport($project_id), $request->file('file'));

        return back()->with('success', 'Gap assessment data imported successfully.');
    }

    /**
     * AJAX endpoint to update the status of a single finding.
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:Open,Closed,In Progress',
        ]);

        $finding = IsoGapAssessment::findOrFail($id);
        $finding->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'status' => $finding->status,
        ]);
    }

    /**
     * Link an evidence file (from the Evidence Hub) to a gap assessment row for traceability.
     */
    public function attachEvidence(Request $request, int $id)
    {
        $finding = IsoGapAssessment::findOrFail($id);

        $validated = $request->validate([
            'evidence_file_id' => 'nullable|exists:evidence_files,id',
        ]);

        if (! empty($validated['evidence_file_id'])) {
            $evidence = EvidenceFile::findOrFail($validated['evidence_file_id']);
            abort_unless($evidence->project_id === $finding->project_id, 422, 'Evidence file does not belong to this project.');
        }

        $finding->update(['evidence_file_id' => $validated['evidence_file_id'] ?? null]);

        return response()->json(['success' => true, 'finding' => $finding->fresh()]);
    }

    /**
     * Generate and stream a PDF audit report for the project.
     */
    public function generateReport(int $project_id)
    {
        $project = Project::findOrFail($project_id);
        $findings = $project->isoGapAssessments()->orderBy('serial_no')->get();

        $stats = $this->buildStats($findings);
        $highFindings = $findings->where('risk_rating', 'High');

        $pdf = Pdf::loadView('iso-gap.report', compact('project', 'findings', 'stats', 'highFindings'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('ISO-27001-Gap-Assessment-Report-'.$project->id.'.pdf');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildStats($findings): array
    {
        $total = $findings->count();
        $high = $findings->where('risk_rating', 'High')->count();
        $medium = $findings->where('risk_rating', 'Medium')->count();
        $low = $findings->where('risk_rating', 'Low')->count();

        return [
            'total' => $total,
            'high_count' => $high,
            'medium_count' => $medium,
            'low_count' => $low,
            'high_pct' => $total > 0 ? round(($high / $total) * 100, 2) : 0,
            'medium_pct' => $total > 0 ? round(($medium / $total) * 100, 2) : 0,
            'low_pct' => $total > 0 ? round(($low / $total) * 100, 2) : 0,
        ];
    }
}
