<?php

namespace App\Http\Controllers;

use App\Mail\ComplianceReportMail;
use App\Models\CustomReportTemplate;
use App\Models\Framework;
use App\Models\GeneratedReport;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\ReportSchedule;
use App\Services\ReportExportService;
use App\Services\ReportGenerationService;
use Illuminate\Http\Request;
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

        $framework = Framework::where('slug', $project->module_type)->firstOrFail();

        $frameworkControls = $framework->controls()->orderBy('control_id')->get();

        $domains = $frameworkControls
            ->groupBy('domain')
            ->map(function ($controls, $name) {
                return [
                    'name' => $name,
                    'total' => $controls->count(),
                    'controls' => $controls,
                ];
            })
            ->values();

        if ($project->module_type === 'pci_dss') {
            $project->load('pciDssDetails.networks', 'pciDssDetails.locations', 'pciDssDetails.components');
        }

        return view('projects.scope', compact('project', 'framework', 'domains'));
    }

    /**
     * Update the scope of the project.
     */
    public function scopeUpdate(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'scope_description' => 'nullable|string|max:2000',
            'in_scope_networks' => 'nullable|array',
            'in_scope_locations' => 'nullable|array',
            'in_scope_components' => 'nullable|array',
        ]);

        $project->update([
            'scope_description' => $validated['scope_description'] ?? null,
        ]);

        if ($project->module_type === 'pci_dss' && $project->pciDssDetails) {
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

        return redirect()->route('projects.gap-assessment', $project);
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

        $totalReportsCount = GeneratedReport::where('project_id', $project->id)->count();

        $frameworkModel = Framework::where('slug', $project->module_type)
            ->where('is_active', true)
            ->first();

        $currentCompliance = 0;
        if ($frameworkModel) {
            $assessment = ProjectAssessment::where('project_id', $project->id)
                ->where('framework_id', $frameworkModel->id)
                ->latest()
                ->first();
            if ($assessment) {
                $stats = $assessment->stats();
                $currentCompliance = $stats['compliancePct'] ?? 0;
            }
        } elseif ($project->module_type === 'pci_dss') {
            $findings = optional($project->pciDssDetails)->findings ?? collect();
            $total = $findings->count();
            $passed = $findings->where('assessment_finding', 'In Place')->count();
            $currentCompliance = $total > 0 ? round(($passed / $total) * 100) : 0;
        }

        $trendData = GeneratedReport::where('project_id', $project->id)
            ->whereNotNull('metadata')
            ->orderBy('generated_at', 'asc')
            ->get()
            ->map(function ($r) {
                $metadata = $r->metadata;

                return [
                    'date' => $r->generated_at ? $r->generated_at->format('Y-m-d H:i') : null,
                    'value' => $metadata['compliance_percentage'] ?? 0,
                ];
            })->filter(function ($item) {
                return $item['date'] !== null;
            })->values();

        return view('projects.reporting.menu', compact(
            'project',
            'availableReports',
            'reportHistory',
            'latestReports',
            'totalReportsCount',
            'currentCompliance',
            'trendData'
        ));
    }

    /**
     * Generate a specific report.
     */
    public function report(Project $project, string $type, Request $request)
    {
        $this->authorize('view', $project);

        $sections = $request->query('sections');
        $filters = $request->query('filters');

        try {
            return $this->reportService->generate($project, $type, [
                'sections' => $sections,
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('projects.reporting', $project)
                ->with('error', 'Failed to generate report: '.$e->getMessage());
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
        if (! in_array($format, ['pdf', 'html'])) {
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
                ->with('error', 'Failed to export report: '.$e->getMessage());
        }
    }

    /**
     * Share a report via email.
     */
    public function shareReport(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $request->validate([
            'email' => 'required|string',
            'subject' => 'nullable|string',
            'message' => 'nullable|string',
            'formats' => 'required|array|min:1',
            'formats.*' => 'in:pdf,html',
        ]);

        $type = 'unified_gap'; // Extracted from the URL route in web.php

        if (! $this->reportService->validateReportType($project, $type)) {
            return redirect()->back()->with('error', "Report type '{$type}' not available.");
        }

        $attachmentsData = [];

        try {
            if (in_array('pdf', $request->formats)) {
                $pdfContent = $this->exportService->generatePdfContent($project, $type);
                $fileName = "{$project->name}-".str_replace('_', '-', $type).'-'.now()->format('Y-m-d').'.pdf';
                $attachmentsData[] = [
                    'data' => $pdfContent,
                    'name' => $fileName,
                    'mime' => 'application/pdf',
                ];
            }

            if (in_array('html', $request->formats)) {
                $htmlContent = $this->exportService->generateHtmlContent($project, $type);
                $fileName = "{$project->name}-".str_replace('_', '-', $type).'-'.now()->format('Y-m-d').'.html';
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
            return redirect()->back()->with('error', 'Failed to share report: '.$e->getMessage());
        }
    }

    /**
     * Schedule recurring report generation.
     */
    public function scheduleReports(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'report_type' => 'required|string',
            'recipient_email' => 'required|email',
            'frequency' => 'required|in:daily,weekly,monthly',
            'format' => 'required|in:pdf,html,both',
        ]);

        $schedule = ReportSchedule::create([
            'project_id' => $project->id,
            'report_type' => $validated['report_type'],
            'recipient_email' => $validated['recipient_email'],
            'frequency' => $validated['frequency'],
            'format' => $validated['format'],
        ]);

        $schedule->calculateNextRun();
        $schedule->save();

        return redirect()->route('projects.reporting', $project)
            ->with('success', 'Report schedule created successfully.');
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
            return redirect()->back()->with('error', 'Failed to delete report schedule: '.$e->getMessage());
        }
    }

    /**
     * Store a custom report template.
     */
    public function storeCustomTemplate(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'name' => 'required|string',
            'report_type' => 'required|string',
            'sections' => 'required|array',
            'filters' => 'sometimes|array',
        ]);

        $template = CustomReportTemplate::create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'report_type' => $validated['report_type'],
            'sections' => $validated['sections'],
            'filters' => $validated['filters'] ?? [],
        ]);

        return redirect()->route('projects.reporting', $project)
            ->with('success', 'Custom template created successfully.');
    }

    /**
     * Download a custom report template.
     */
    public function downloadCustomTemplate(Project $project, CustomReportTemplate $template, Request $request)
    {
        $this->authorize('view', $project);

        $format = $request->query('format', 'pdf');

        if (! in_array($format, ['pdf', 'html'])) {
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
                ->with('error', 'Failed to export report: '.$e->getMessage());
        }
    }

    /**
     * Delete a custom report template.
     */
    public function deleteCustomTemplate(Project $project, CustomReportTemplate $template)
    {
        $this->authorize('view', $project);

        $template->delete();

        return redirect()->route('projects.reporting', $project)
            ->with('success', 'Custom template deleted successfully.');
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

        if (! $pciDetails) {
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
