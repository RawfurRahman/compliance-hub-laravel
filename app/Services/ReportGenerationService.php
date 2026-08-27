<?php

namespace App\Services;

use App\Models\GeneratedReport;
use App\Models\Project;
use App\Reports\Generators\PciDssRocGenerator;
use App\Reports\Generators\ReportGenerator;
use App\Reports\Generators\UnifiedAssessmentGenerator;
use App\Support\Reports\ReportRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportGenerationService
{
    /**
     * Generate a report for a project.
     */
    public function generate(Project $project, string $type, array $options = []): View
    {
        if (! $this->validateReportType($project, $type)) {
            abort(404, "Report type '{$type}' not available for this project.");
        }

        // Log report generation
        // Dispatch to appropriate generator based on report type
        $generator = $this->getReportGenerator($type);
        $view = $generator->generate($project, $type, $options);

        $viewData = $view->getData();
        $compliancePercentage = 0;
        if (isset($viewData['stats']['compliancePct'])) {
            $compliancePercentage = $viewData['stats']['compliancePct'];
        } elseif (isset($viewData['complianceMetrics']['compliance_percentage'])) {
            $compliancePercentage = $viewData['complianceMetrics']['compliance_percentage'];
        }

        $metadata = array_merge($options['metadata'] ?? [], [
            'compliance_percentage' => $compliancePercentage,
        ]);

        $report = GeneratedReport::create([
            'project_id' => $project->id,
            'report_type' => $type,
            'framework_slug' => $project->module_type,
            'framework_version' => $options['framework_version'] ?? null,
            'generated_by' => Auth::id(),
            'status' => 'final',
            'metadata' => $metadata,
            'generated_at' => now(),
        ]);

        return $view;
    }

    /**
     * Get the appropriate report generator for the specified type.
     */
    protected function getReportGenerator(string $type): ReportGenerator
    {
        return match (true) {
            $type === 'pci_dss_roc' => new PciDssRocGenerator,
            $type === 'unified_gap' || $type === 'unified_final' => new UnifiedAssessmentGenerator,
            default => throw new \Exception("No generator found for report type: {$type}"),
        };
    }

    /**
     * Get all available reports for a project.
     */
    public function getAvailableReports(Project $project): Collection
    {
        return collect(ReportRegistry::getAvailableReports($project));
    }

    /**
     * Validate if a report type is available for the project.
     */
    public function validateReportType(Project $project, string $type): bool
    {
        $available = $this->getAvailableReports($project)
            ->pluck('type')
            ->toArray();

        return in_array($type, $available);
    }

    /**
     * Get report history for a project.
     */
    public function getReportHistory(Project $project, int $limit = 10): Collection
    {
        return $project->reports()
            ->orderBy('generated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the most recent report of a specific type.
     */
    public function getLatestReport(Project $project, string $type): ?GeneratedReport
    {
        return $project->reports()
            ->where('report_type', $type)
            ->orderBy('generated_at', 'desc')
            ->first();
    }
}
