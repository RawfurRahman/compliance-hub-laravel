<?php

namespace App\Http\Controllers;

use App\Imports\IsoGapAssessmentImport;
use App\Models\IsoGapAssessment;
use App\Models\Project;
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
        $project  = Project::findOrFail($project_id);
        $findings = $project->isoGapAssessments()->orderBy('serial_no')->get();

        $stats = $this->buildStats($findings);

        return view('iso-gap.index', compact('project', 'findings', 'stats'));
    }

    /**
     * Handle Excel import for a project's gap assessment data.
     */
    public function import(Request $request, int $project_id)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

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
            'status'  => $finding->status,
        ]);
    }

    /**
     * Generate and stream a PDF audit report for the project.
     */
    public function generateReport(int $project_id)
    {
        $project  = Project::findOrFail($project_id);
        $findings = $project->isoGapAssessments()->orderBy('serial_no')->get();

        $stats        = $this->buildStats($findings);
        $highFindings = $findings->where('risk_rating', 'High');

        $pdf = Pdf::loadView('iso-gap.report', compact('project', 'findings', 'stats', 'highFindings'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download('ISO-27001-Gap-Assessment-Report-' . $project->id . '.pdf');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildStats($findings): array
    {
        $total  = $findings->count();
        $high   = $findings->where('risk_rating', 'High')->count();
        $medium = $findings->where('risk_rating', 'Medium')->count();
        $low    = $findings->where('risk_rating', 'Low')->count();

        return [
            'total'        => $total,
            'high_count'   => $high,
            'medium_count' => $medium,
            'low_count'    => $low,
            'high_pct'     => $total > 0 ? round(($high   / $total) * 100, 2) : 0,
            'medium_pct'   => $total > 0 ? round(($medium / $total) * 100, 2) : 0,
            'low_pct'      => $total > 0 ? round(($low    / $total) * 100, 2) : 0,
        ];
    }
}
