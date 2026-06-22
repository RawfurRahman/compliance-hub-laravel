<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ReportSchedule;
use App\Models\CustomReportTemplate;
use App\Mail\ComplianceReportMail;

use App\Services\ReportGenerationService;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;


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

        // Get active report schedules
        $schedules = $project->reportSchedules()->orderBy('created_at', 'desc')->get();

        // Get custom report templates
        $customTemplates = $project->customReportTemplates()->orderBy('created_at', 'desc')->get();

        // Stage 6 - Calculate reporting dashboard metrics
        $allReports = $project->reports()->orderBy('generated_at', 'asc')->get();
        $totalReportsCount = $allReports->count();
        $activeSchedulesCount = $schedules->count();
        $customTemplatesCount = $customTemplates->count();

        // Current posture calculation
        $currentCompliance = 0;
        if ($project->module_type === 'pci_dss') {
            $findings = optional($project->pciDssDetails)->findings ?? collect();
            $totalReqs = $findings->count();
            $passedReqs = $findings->where('assessment_finding', 'In Place')->count();
            $currentCompliance = $totalReqs > 0 ? round(($passedReqs / $totalReqs) * 100, 2) : 0;
        } else {
            $gap = $project->gapAssessment;
            if ($gap) {
                $currentCompliance = $gap->stats()['compliancePct'] ?? 0;
            }
        }

        // Trend data: map generated reports to their compliance percentage snapshots
        $trendData = $allReports->map(function ($r) {
            return [
                'label' => $r->generated_at ? $r->generated_at->format('M d') : $r->created_at->format('M d'),
                'value' => (float)($r->metadata['compliance_percentage'] ?? 0),
                'type' => ucwords(str_replace('_', ' ', $r->report_type)),
            ];
        });

        // Distribution of report types
        $reportTypesDist = $allReports->groupBy('report_type')->map(function ($group) {
            return $group->count();
        });

        // Distribution of formats
        $formatDist = ['pdf' => 0, 'html' => 0];
        foreach ($allReports as $r) {
            $formats = $r->exported_formats ?? [];
            foreach ($formats as $f) {
                if (isset($formatDist[$f])) {
                    $formatDist[$f]++;
                }
            }
        }

        return view('projects.reporting.menu', compact(
            'project',
            'availableReports',
            'reportHistory',
            'latestReports',
            'schedules',
            'customTemplates',
            'totalReportsCount',
            'activeSchedulesCount',
            'customTemplatesCount',
            'currentCompliance',
            'trendData',
            'reportTypesDist',
            'formatDist'
        ));
    }

    /**
     * Generate a specific report.
     */
    public function report(Project $project, string $type, Request $request)
    {
        $this->authorize('view', $project);

        try {
            $options = [
                'sections' => $request->query('sections'),
                'filters' => $request->query('filters'),
            ];
            return $this->reportService->generate($project, $type, $options);
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
            $sections = $request->query('sections');
            $filters = $request->query('filters');

            if ($format === 'pdf') {
                return $this->exportService->exportPdf($project, $type, $sections, $filters);
            } elseif ($format === 'html') {
                return $this->exportService->exportHtml($project, $type, $sections, $filters);
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

    /**
     * Share a compliance report via email.
     */
    public function shareReport(Request $request, Project $project, string $type)
    {
        $this->authorize('view', $project);

        $request->validate([
            'email' => 'required|string',
            'subject' => 'nullable|string',
            'message' => 'nullable|string',
            'formats' => 'required|array|min:1',
            'formats.*' => 'in:pdf,html',
        ]);

        if (!$this->reportService->validateReportType($project, $type)) {
            return redirect()->back()->with('error', "Report type '{$type}' not available.");
        }

        $attachmentsData = [];

        try {
            if (in_array('pdf', $request->formats)) {
                $pdfContent = $this->exportService->generatePdfContent($project, $type);
                $fileName = "{$project->name}-" . str_replace('_', '-', $type) . "-" . now()->format('Y-m-d') . ".pdf";
                $attachmentsData[] = [
                    'data' => $pdfContent,
                    'name' => $fileName,
                    'mime' => 'application/pdf',
                ];
            }

            if (in_array('html', $request->formats)) {
                $htmlContent = $this->exportService->generateHtmlContent($project, $type);
                $fileName = "{$project->name}-" . str_replace('_', '-', $type) . "-" . now()->format('Y-m-d') . ".html";
                $attachmentsData[] = [
                    'data' => $htmlContent,
                    'name' => $fileName,
                    'mime' => 'text/html',
                ];
            }

            $availableReports = $this->reportService->getAvailableReports($project);
            $reportLabel = collect($availableReports)->firstWhere('type', $type)['label'] ?? ucwords(str_replace('_', ' ', $type));

            // Support comma-separated emails
            $emails = array_map('trim', explode(',', $request->email));

            Mail::to($emails)->send(new ComplianceReportMail($project->name, $reportLabel, $request->message, $attachmentsData));

            return redirect()->back()->with('success', 'Report shared successfully via email.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to share report: ' . $e->getMessage());
        }
    }

    /**
     * Store a new report schedule.
     */
    public function storeSchedule(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $request->validate([
            'report_type' => 'required|string',
            'recipient_email' => 'required|string',
            'frequency' => 'required|in:daily,weekly,monthly',
            'format' => 'required|in:pdf,html,both',
        ]);

        if (!$this->reportService->validateReportType($project, $request->report_type)) {
            return redirect()->back()->with('error', "Report type '{$request->report_type}' not available.");
        }

        try {
            $schedule = new ReportSchedule([
                'project_id' => $project->id,
                'report_type' => $request->report_type,
                'recipient_email' => $request->recipient_email,
                'frequency' => $request->frequency,
                'format' => $request->format,
            ]);

            $schedule->calculateNextRun();
            $schedule->save();

            return redirect()->back()->with('success', 'Report schedule created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create report schedule: ' . $e->getMessage());
        }
    }

    /**
     * Delete a report schedule.
     */
    public function destroySchedule(Project $project, ReportSchedule $schedule)
    {
        $this->authorize('view', $project);
        abort_if($schedule->project_id !== $project->id, 403);

        try {
            $schedule->delete();
            return redirect()->back()->with('success', 'Report schedule deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete report schedule: ' . $e->getMessage());
        }
    }

    /**
     * Store a new custom report template configuration.
     */
    public function storeCustomTemplate(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $request->validate([
            'name' => 'required|string|max:255',
            'report_type' => 'required|string',
            'sections' => 'required|array|min:1',
            'sections.*' => 'in:executive_summary,metrics,table,detailed_findings',
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|in:all,compliant,non_compliant',
            'filters.risk' => 'nullable|in:all,High,Medium,Low',
        ]);

        if (!$this->reportService->validateReportType($project, $request->report_type)) {
            return redirect()->back()->with('error', "Report type '{$request->report_type}' not available.");
        }

        try {
            $template = new CustomReportTemplate([
                'project_id' => $project->id,
                'name' => $request->name,
                'report_type' => $request->report_type,
                'sections' => $request->sections,
                'filters' => $request->filters,
            ]);

            $template->save();

            return redirect()->back()->with('success', 'Custom report template saved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to save template: ' . $e->getMessage());
        }
    }

    /**
     * Delete a custom report template.
     */
    public function destroyCustomTemplate(Project $project, CustomReportTemplate $template)
    {
        $this->authorize('view', $project);
        abort_if($template->project_id !== $project->id, 403);

        try {
            $template->delete();
            return redirect()->back()->with('success', 'Custom report template deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete template: ' . $e->getMessage());
        }
    }

    /**
     * Download a report using a saved custom report template.
     */
    public function downloadCustomTemplate(Project $project, CustomReportTemplate $template, Request $request)
    {
        $this->authorize('view', $project);
        abort_if($template->project_id !== $project->id, 403);

        $format = $request->query('format', 'pdf');

        if (!in_array($format, ['pdf', 'html'])) {
            return redirect()->route('projects.reporting', $project)
                ->with('error', 'Invalid export format.');
        }

        try {
            if ($format === 'pdf') {
                return $this->exportService->exportPdf($project, $template->report_type, $template->sections, $template->filters);
            } elseif ($format === 'html') {
                return $this->exportService->exportHtml($project, $template->report_type, $template->sections, $template->filters);
            }
        } catch (\Exception $e) {
            return redirect()->route('projects.reporting', $project)
                ->with('error', 'Failed to export report: ' . $e->getMessage());
        }
    }
}


