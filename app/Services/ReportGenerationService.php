<?php

namespace App\Services;

use App\Models\Project;
use App\Models\GeneratedReport;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Support\Reports\ReportRegistry;

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

        // Calculate current compliance percentage for snapshotting
        $compliancePct = 0;
        if ($type === 'unified_gap' || $type === 'unified_final') {
            $assessType = $type === 'unified_gap' ? 'Gap' : 'Final';
            $frameworkModel = \App\Models\Framework::where('slug', $project->module_type)
                ->where('is_active', true)
                ->first();
            if ($frameworkModel) {
                $assessment = \App\Models\ProjectAssessment::where('project_id', $project->id)
                    ->where('framework_id', $frameworkModel->id)
                    ->where('type', $assessType)
                    ->first();
                if ($assessment) {
                    $compliancePct = $assessment->stats()['compliancePct'] ?? 0;
                }
            }
        } elseif ($type === 'pci_dss_gap') {
            $totalControls = $project->gapControls()->count();
            $completedControls = $project->gapControls()->where('status', 'Done')->count();
            $compliancePct = $totalControls > 0 ? round(($completedControls / $totalControls) * 100) : 0;
        } else {
            // PCI DSS ROC / AOC
            $findings = optional($project->pciDssDetails)->findings ?? collect();
            $totalRequirements = $findings->count();
            $passedRequirements = $findings->where('assessment_finding', 'In Place')->count();
            $compliancePct = $totalRequirements > 0
                ? round(($passedRequirements / $totalRequirements) * 100, 2)
                : 0;
        }

        $meta = $options['metadata'] ?? [];
        $meta['compliance_percentage'] = $compliancePct;

        // Log report generation
        $report = GeneratedReport::create([
            'project_id' => $project->id,
            'report_type' => $type,
            'framework_slug' => $project->module_type,
            'framework_version' => $options['framework_version'] ?? '4.0.1',
            'generated_by' => Auth::id(),
            'status' => 'final',
            'metadata' => $meta,
        ]);

        return $this->getReportView($project, $type, $options['sections'] ?? null, $options['filters'] ?? null);
    }

    /**
     * Get the report view for the specified type.
     */
    public function getReportView(Project $project, string $type, ?array $sections = null, ?array $filters = null): View
    {
        if ($type === 'unified_gap' || $type === 'unified_final') {
            $assessType = $type === 'unified_gap' ? 'Gap' : 'Final';
            $frameworkModel = \App\Models\Framework::where('slug', $project->module_type)
                ->where('is_active', true)
                ->firstOrFail();

            $assessment = \App\Models\ProjectAssessment::where('project_id', $project->id)
                ->where('framework_id', $frameworkModel->id)
                ->where('type', $assessType)
                ->firstOrFail();

            $assessment->load(['findings.frameworkControl', 'findings.evidence', 'project', 'framework']);

            $framework = $assessment->framework;
            $stats = $assessment->stats();
            $findings = $assessment->findings;

            if ($filters) {
                if (isset($filters['status']) && $filters['status'] !== 'all') {
                    $isCompliant = $filters['status'] === 'compliant';
                    $findings = $findings->where('is_compliant', $isCompliant);
                }
                if (isset($filters['risk']) && $filters['risk'] !== 'all') {
                    $findings = $findings->where('risk_rating', $filters['risk']);
                }
            }

            return view('assessments.report-pdf', compact(
                'assessment', 'project', 'framework', 'stats', 'findings', 'sections', 'filters'
            ));
        }

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

        // Apply filters to PCI DSS findings
        if ($filters) {
            if (isset($filters['status']) && $filters['status'] !== 'all') {
                $statusVal = $filters['status'] === 'compliant' ? 'In Place' : 'Not in Place';
                $findings = $findings->filter(function($f) use ($statusVal) {
                    return $f->assessment_finding === $statusVal;
                });
            }
        }

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

            return view('pci.gap', compact('project', 'departments', 'complianceMetrics', 'sections', 'filters'));
        }

        if ($type === 'pci_dss_aoc') {
            return view('pci.aoc', compact('project', 'complianceMetrics', 'paymentChannels', 'sections', 'filters'));
        }

        // Default to ROC Report
        return view('pci.report', compact('project', 'requirements', 'findings', 'paymentChannels', 'complianceMetrics', 'sections', 'filters'));
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
