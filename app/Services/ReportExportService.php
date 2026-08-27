<?php

namespace App\Services;

use App\Models\GeneratedReport;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

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
    public function exportHtml(Project $project, string $type, ?array $sections = null, ?array $filters = null): View
    {
        $content = $this->getReportContent($project, $type, $sections, $filters);

        // Update exported formats tracking
        $this->trackExport($project, $type, 'html');

        return view($content['view'], $content['data']);
    }

    /**
     * Get report content (view and data).
     */
    protected function getReportContent(Project $project, string $type, ?array $sections = null, ?array $filters = null): array
    {
        $options = [];
        if ($sections !== null) {
            $options['sections'] = $sections;
        }
        if ($filters !== null) {
            $options['filters'] = $filters;
        }

        $view = $this->reportGenerationService->generate($project, $type, $options);

        return [
            'view' => $view->name(),
            'data' => $view->getData(),
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
            if (! in_array($format, $formats)) {
                $formats[] = $format;
                $report->update(['exported_formats' => $formats]);
            }
        }
    }

    /**
     * Generate PDF content for the report.
     */
    public function generatePdfContent(Project $project, string $type): string
    {
        $content = $this->getReportContent($project, $type);
        $pdf = Pdf::loadView($content['view'], $content['data'])
            ->setPaper('a4')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'isRemoteEnabled' => false,
                'chroot' => public_path(),
            ]);

        return $pdf->output();
    }

    /**
     * Generate HTML string content (for email attachments)
     */
    public function generateHtmlContent(Project $project, string $type): string
    {
        $content = $this->getReportContent($project, $type);

        return view($content['view'], $content['data'])->render();
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
