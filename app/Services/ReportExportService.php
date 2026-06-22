<?php

namespace App\Services;

use App\Models\Project;
use App\Models\GeneratedReport;
use App\Models\Department;
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
    public function exportPdf(Project $project, string $type, ?array $sections = null, ?array $filters = null): Response
    {
        $content = $this->getReportContent($project, $type, $sections, $filters);
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
    public function exportHtml(Project $project, string $type, ?array $sections = null, ?array $filters = null): \Illuminate\View\View
    {
        $content = $this->getReportContent($project, $type, $sections, $filters);

        // Update exported formats tracking
        $this->trackExport($project, $type, 'html');

        return view($content['view'], $content['data']);
    }

    /**
     * Generate PDF content as binary/string.
     */
    public function generatePdfContent(Project $project, string $type, ?array $sections = null, ?array $filters = null): string
    {
        $content = $this->getReportContent($project, $type, $sections, $filters);

        $pdf = Pdf::loadView($content['view'], $content['data'])
            ->setPaper('a4')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'isRemoteEnabled' => false,
                'chroot' => public_path(),
            ]);

        $this->trackExport($project, $type, 'pdf');

        return $pdf->output();
    }

    /**
     * Generate HTML content as string.
     */
    public function generateHtmlContent(Project $project, string $type, ?array $sections = null, ?array $filters = null): string
    {
        $content = $this->getReportContent($project, $type, $sections, $filters);

        $this->trackExport($project, $type, 'html');

        return view($content['view'], $content['data'])->render();
    }


    /**
     * Get report content (view and data).
     */
    protected function getReportContent(Project $project, string $type, ?array $sections = null, ?array $filters = null): array
    {
        // Validate report type
        if (!$this->reportGenerationService->validateReportType($project, $type)) {
            abort(404, "Report type '{$type}' not available for this project.");
        }

        $view = $this->reportGenerationService->getReportView($project, $type, $sections, $filters);

        return [
            'view' => $view->name(),
            'data' => $view->getData(),
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
