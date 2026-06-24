<?php

namespace App\Services;

use App\Models\Project;
use App\Models\GeneratedReport;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportExportService
{
    protected $reportGenerationService;

    public function __construct(ReportGenerationService $reportGenerationService)
    {
        $this->reportGenerationService = $reportGenerationService;
    }

    /**
     * Export report as PDF.
     */
    public function exportPdf(Project $project, string $type): Response
    {
        $content = $this->getReportContent($project, $type);
        $fileName = $this->generateFileName($project, $type, 'pdf');

        $pdf = Pdf::loadView($content['view'], $content['data'])
            ->setPaper('a4')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'isRemoteEnabled' => false,
                'chroot' => public_path(),
            ]);

        // Update exported formats tracking
        $this->trackExport($project, $type, 'pdf');

        return $pdf->download($fileName);
    }

    /**
     * Export report as HTML (View).
     */
    public function exportHtml(Project $project, string $type): \Illuminate\View\View
    {
        $content = $this->getReportContent($project, $type);

        // Update exported formats tracking
        $this->trackExport($project, $type, 'html');

        return view($content['view'], $content['data']);
    }

    /**
     * Get report content (view and data).
     */
    protected function getReportContent(Project $project, string $type): array
    {
        // Validate report type
        if (!$this->reportGenerationService->validateReportType($project, $type)) {
            abort(404, "Report type '{$type}' not available for this project.");
        }

        // Ensure PCI DSS project
        if ($project->module_type !== 'pci_dss') {
            abort(404);
        }

        // Eager load all necessary relationships
        $project->load(
            'pciDssDetails.pciSscProducts',
            'pciDssDetails.tpsps',
            'pciDssDetails.networks',
            'pciDssDetails.locations',
            'pciDssDetails.components',
            'pciDssDetails.externalScans',
            'pciDssDetails.internalScans',
            'pciDssDetails.findings.requirement'
        );

        // Get all PCI DSS requirements
        $requirements = \App\Models\PciDssRequirement::all()->sortBy('req_num', SORT_NATURAL);

        // Get findings keyed by requirement
        $findings = optional($project->pciDssDetails)->findings->keyBy('pci_dss_requirement_id') ?? collect();

        // Payment channels
        $paymentChannels = config('compliance.pci_dss.payment_channels', []);

        // Calculate compliance metrics
        $complianceMetrics = $this->calculateComplianceMetrics($project, $findings);

        return [
            'view' => 'pci.report',
            'data' => compact('project', 'requirements', 'findings', 'paymentChannels', 'complianceMetrics'),
        ];
    }

    /**
     * Calculate compliance metrics for the report.
     */
    protected function calculateComplianceMetrics(Project $project, $findings): array
    {
        $totalRequirements = $findings->count();
        $passedRequirements = $findings->where('assessment_finding', 'In Place')->count();
        $failedRequirements = $findings->where('assessment_finding', 'Not in Place')->count();
        $notTestedRequirements = $findings->where('assessment_finding', 'Not Tested')->count();
        $naRequirements = $findings->where('assessment_finding', 'Not Applicable')->count();

        $compliancePercentage = $totalRequirements > 0
            ? round(($passedRequirements / $totalRequirements) * 100, 2)
            : 0;

        return [
            'total_requirements' => $totalRequirements,
            'passed' => $passedRequirements,
            'failed' => $failedRequirements,
            'not_tested' => $notTestedRequirements,
            'not_applicable' => $naRequirements,
            'compliance_percentage' => $compliancePercentage,
            'is_compliant' => $failedRequirements === 0 && $notTestedRequirements === 0,
        ];
    }

    /**
     * Track exported format for the report.
     */
    protected function trackExport(Project $project, string $type, string $format): void
    {
        $report = GeneratedReport::where('project_id', $project->id)
            ->where('report_type', $type)
            ->orderBy('generated_at', 'desc')
            ->first();

        if ($report) {
            $formats = $report->exported_formats ?? [];
            if (!in_array($format, $formats)) {
                $formats[] = $format;
                $report->update(['exported_formats' => $formats]);
            }
        }
    }

    /**
     * Generate file name for export.
     */
    protected function generateFileName(Project $project, string $type, string $format): string
    {
        $typeName = str_replace('_', '-', $type);
        $date = now()->format('Y-m-d');
        return "{$project->name}-{$typeName}-{$date}.{$format}";
    }
}
