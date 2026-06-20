<?php

namespace App\Services;

use App\Models\Project;
use App\Models\GeneratedReport;
use App\Models\Department;
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
            'framework_version' => $options['framework_version'] ?? '4.0.1',
            'generated_by' => Auth::id(),
            'status' => 'final',
            'metadata' => $options['metadata'] ?? null,
        ]);

        return $this->getReportView($project, $type);
    }

    /**
     * Get the report view for the specified type.
     */
    protected function getReportView(Project $project, string $type): View
    {
        if ($project->module_type !== 'pci_dss') {
            abort(404);
        }

        // Eager load necessary relationships
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

        $requirements = \App\Models\PciDssRequirement::all()->sortBy('req_num', SORT_NATURAL);
        $findings = optional($project->pciDssDetails)->findings->keyBy('pci_dss_requirement_id') ?? collect();
        $paymentChannels = config('compliance.pci_dss.payment_channels', []);

        // Calculate standard PCI metrics
        $totalRequirements = $findings->count();
        $passedRequirements = $findings->where('assessment_finding', 'In Place')->count();
        $failedRequirements = $findings->where('assessment_finding', 'Not in Place')->count();
        $notTestedRequirements = $findings->where('assessment_finding', 'Not Tested')->count();
        $naRequirements = $findings->where('assessment_finding', 'Not Applicable')->count();

        $compliancePercentage = $totalRequirements > 0
            ? round(($passedRequirements / $totalRequirements) * 100, 2)
            : 0;

        $complianceMetrics = [
            'total_requirements' => $totalRequirements,
            'passed' => $passedRequirements,
            'failed' => $failedRequirements,
            'not_tested' => $notTestedRequirements,
            'not_applicable' => $naRequirements,
            'compliance_percentage' => $compliancePercentage,
            'is_compliant' => $failedRequirements === 0 && $notTestedRequirements === 0,
        ];

        $departments = collect();

        // Specific modifications for Gap Report
        if ($type === 'pci_dss_gap') {
            $departments = Department::with(['gapControls' => function($q) use ($project) {
                $q->where('project_id', $project->id)->with('evidenceFiles');
            }])->get()->filter(function($dept) {
                return $dept->gapControls->count() > 0;
            })->values();

            // Override compliance metrics using GapControls
            $totalControls = $project->gapControls()->count();
            $completedControls = $project->gapControls()->where('status', 'Done')->count();
            $pendingControls = $totalControls - $completedControls;
            $gapProgress = $totalControls > 0 ? round(($completedControls / $totalControls) * 100) : 0;

            $complianceMetrics = [
                'total_requirements' => $totalControls,
                'passed' => $completedControls,
                'failed' => $pendingControls,
                'not_tested' => 0,
                'not_applicable' => 0,
                'compliance_percentage' => $gapProgress,
                'is_compliant' => $pendingControls === 0,
            ];

            return view('pci.gap', compact('project', 'departments', 'complianceMetrics'));
        }

        if ($type === 'pci_dss_aoc') {
            return view('pci.aoc', compact('project', 'complianceMetrics', 'paymentChannels'));
        }

        // Default to ROC Report
        return view('pci.report', compact('project', 'requirements', 'findings', 'paymentChannels', 'complianceMetrics'));
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
                'disabled' => false,
            ]);

            $reports->push([
                'type' => 'pci_dss_aoc',
                'label' => 'Attestation of Compliance (AOC)',
                'description' => 'Signed attestation document for validation authorities',
                'version' => '4.0.1',
                'icon' => 'fa-certificate',
                'color' => 'emerald',
                'disabled' => false,
            ]);

            $reports->push([
                'type' => 'pci_dss_gap',
                'label' => 'Gap Assessment Report',
                'description' => 'Analysis of non-compliant requirements and remediation steps',
                'version' => '4.0.1',
                'icon' => 'fa-chart-bar',
                'color' => 'amber',
                'disabled' => false,
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
