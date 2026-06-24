<?php

namespace App\Services;

use App\Models\Project;
use App\Models\GeneratedReport;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ReportGenerationService
{
    /**
     * Generate a report for a project.
     */
    public function generate(Project $project, string $type, array $options = []): View
    {
        if (!$this->validateReportType($project, $type)) {
            abort(404, "Report type '{$type}' not available for this project.");
        }

        // Log report generation
        $report = GeneratedReport::create([
            'project_id' => $project->id,
            'report_type' => $type,
            'framework_slug' => $project->module_type,
            'framework_version' => $options['framework_version'] ?? null,
            'generated_by' => Auth::id(),
            'status' => 'final',
            'metadata' => $options['metadata'] ?? null,
        ]);

        // Dispatch to appropriate generator based on report type
        return $this->getReportView($project, $type);
    }

    /**
     * Get the report view for the specified type.
     */
    protected function getReportView(Project $project, string $type): View
    {
        // Ensure the project is a PCI DSS module type
        if ($project->module_type !== 'pci_dss') {
            abort(404);
        }

        // Eager load all necessary relationships for the report
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

        // Get all PCI DSS requirements, sorted naturally
        $requirements = \App\Models\PciDssRequirement::all()->sortBy('req_num', SORT_NATURAL);

        // Get the project's findings, keyed by the requirement ID for easy lookup
        $findings = optional($project->pciDssDetails)->findings->keyBy('pci_dss_requirement_id') ?? collect();

        // Get the list of payment channels from the configuration
        $paymentChannels = config('compliance.pci_dss.payment_channels', []);

        // Return the dedicated report view with all the necessary data
        return view('pci.report', compact('project', 'requirements', 'findings', 'paymentChannels'));
    }

    /**
     * Get all available reports for a project.
     */
    public function getAvailableReports(Project $project): Collection
    {
        $reports = collect();

        if ($project->module_type === 'pci_dss') {
            $reports->push([
                'type' => 'pci_dss_roc',
                'label' => 'Report on Compliance (ROC)',
                'description' => 'Official PCI DSS Assessment Report - Version 4.0.1',
                'version' => '4.0.1',
                'icon' => 'fa-file-pdf',
                'color' => 'sky',
            ]);

            $reports->push([
                'type' => 'pci_dss_aoc',
                'label' => 'Attestation of Compliance (AOC)',
                'description' => 'Signed attestation document for validation authorities',
                'version' => '4.0.1',
                'icon' => 'fa-certificate',
                'color' => 'emerald',
                'disabled' => true, // Not yet implemented
            ]);

            $reports->push([
                'type' => 'pci_dss_gap',
                'label' => 'Gap Assessment Report',
                'description' => 'Analysis of non-compliant requirements and remediation steps',
                'version' => '4.0.1',
                'icon' => 'fa-chart-bar',
                'color' => 'amber',
                'disabled' => true, // Not yet implemented
            ]);
        }

        return $reports;
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
