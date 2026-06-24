<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ReportGenerationService;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectHubController extends Controller
{
    protected $reportService;
    protected $exportService;

    public function __construct(ReportGenerationService $reportService, ReportExportService $exportService)
    {
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    /**
     * Show the project hub dashboard.
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        // Eager load relationships for the dashboard
        $project->load(
            'pciDssDetails',
            'evidence',
            'meetings',
            'assignedUsers.roles'
        );

        // Calculate project statistics
        $stats = $this->calculateProjectStats($project);

        return view('projects.hub', compact('project', 'stats'));
    }

    /**
     * Show the scope management view.
     */
    public function scope(Project $project)
    {
        $this->authorize('view', $project);

        if ($project->module_type === 'pci_dss') {
            $project->load('pciDssDetails.networks', 'pciDssDetails.locations', 'pciDssDetails.components');
            return view('projects.scope', compact('project'));
        }

        abort(404, 'Scope management not available for this framework.');
    }

    /**
     * Update the scope of the project.
     */
    public function scopeUpdate(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        if ($project->module_type !== 'pci_dss') {
            abort(404);
        }

        // Validate and update scope-related data
        $validated = $request->validate([
            'scope_description' => 'nullable|string|max:2000',
            'in_scope_networks' => 'nullable|array',
            'in_scope_locations' => 'nullable|array',
            'in_scope_components' => 'nullable|array',
        ]);

        // Update PCI DSS details if they exist
        if ($project->pciDssDetails) {
            $project->pciDssDetails->update([
                'scope_description' => $validated['scope_description'] ?? null,
            ]);
        }

        return redirect()->route('projects.scope', $project)
            ->with('success', 'Project scope updated successfully.');
    }

    /**
     * Show the gap assessment view.
     */
    public function gapAssessment(Project $project)
    {
        $this->authorize('view', $project);

        if ($project->module_type !== 'pci_dss') {
            abort(404, 'Gap assessment not available for this framework.');
        }

        // Load findings and requirements for gap analysis
        $project->load('pciDssDetails.findings.requirement');

        // Calculate compliance status by requirement
        $requirementStatus = $this->calculateRequirementStatus($project);

        return view('projects.gap-assessment', compact('project', 'requirementStatus'));
    }

    /**
     * Show the reporting menu.
     */
    public function reporting(Project $project)
    {
        $this->authorize('view', $project);

        // Get available reports for this project
        $availableReports = $this->reportService->getAvailableReports($project);

        // Get report history
        $reportHistory = $this->reportService->getReportHistory($project, 10);

        // Get latest report for each type
        $latestReports = collect();
        foreach ($availableReports as $report) {
            $latest = $this->reportService->getLatestReport($project, $report['type']);
            if ($latest) {
                $latestReports[$report['type']] = $latest;
            }
        }

        return view('projects.reporting.menu', compact(
            'project',
            'availableReports',
            'reportHistory',
            'latestReports'
        ));
    }

    /**
     * Generate a specific report.
     */
    public function report(Project $project, string $type)
    {
        $this->authorize('view', $project);

        try {
            return $this->reportService->generate($project, $type);
        } catch (\Exception $e) {
            return redirect()->route('projects.reporting', $project)
                ->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Download a report in the specified format.
     */
    public function downloadReport(Project $project, string $type, Request $request)
    {
        $this->authorize('view', $project);

        $format = $request->query('format', 'pdf');

        // Validate format
        if (!in_array($format, ['pdf', 'html'])) {
            return redirect()->route('projects.reporting', $project)
                ->with('error', 'Invalid export format.');
        }

        try {
            if ($format === 'pdf') {
                return $this->exportService->exportPdf($project, $type);
            } elseif ($format === 'html') {
                return $this->exportService->exportHtml($project, $type);
            }
        } catch (\Exception $e) {
            return redirect()->route('projects.reporting', $project)
                ->with('error', 'Failed to export report: ' . $e->getMessage());
        }
    }

    /**
     * Calculate project statistics.
     */
    protected function calculateProjectStats(Project $project): array
    {
        $pciDetails = $project->pciDssDetails;

        $stats = [
            'total_evidence' => $project->evidence->count(),
            'pending_evidence' => $project->evidence->where('status', 'pending')->count(),
            'total_meetings' => $project->meetings->count(),
            'team_members' => $project->assignedUsers->count(),
        ];

        if ($pciDetails) {
            $stats['total_requirements'] = 6; // PCI DSS has 6 pillars
            $stats['completed_requirements'] = $pciDetails->findings->where('status', 'pass')->count();
        }

        return $stats;
    }

    /**
     * Calculate requirement compliance status.
     */
    protected function calculateRequirementStatus(Project $project): array
    {
        $pciDetails = $project->pciDssDetails;

        if (!$pciDetails) {
            return [];
        }

        $findings = $pciDetails->findings->groupBy('pci_dss_requirement_id');

        return $findings->map(function ($reqs) {
            return [
                'total' => $reqs->count(),
                'passed' => $reqs->where('status', 'pass')->count(),
                'failed' => $reqs->where('status', 'fail')->count(),
                'not_tested' => $reqs->where('status', 'not_tested')->count(),
            ];
        })->toArray();
    }
}
