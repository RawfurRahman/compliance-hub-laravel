<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\ProjectPciDssDetail;
use App\Models\PciSscProduct;
use App\Models\PciTpsp;
use App\Models\PciNetwork;
use App\Models\PciLocation;
use App\Models\PciComponent;
use App\Models\PciExternalScan;
use App\Models\PciInternalScan;
use App\Models\PciDssFinding;
use App\Models\Project;
use App\Modules\RiskManagement\Services\DashboardMetricsService;
use App\Modules\RiskManagement\Resources\Dashboard\KpiResource;
use App\Modules\RiskManagement\Resources\Dashboard\HeatmapCellResource;
use App\Modules\RiskManagement\Resources\Dashboard\TopRiskResource;
use App\Modules\RiskManagement\Resources\Dashboard\InherentVsResidualResource;
use App\Modules\RiskManagement\Resources\Dashboard\ControlEffectivenessResource;
use App\Modules\RiskManagement\Resources\Dashboard\ComplianceScorecardResource;
use App\Modules\RiskManagement\Resources\Dashboard\MaturityScoreResource;
use App\Modules\RiskManagement\Resources\Dashboard\RiskByDepartmentResource;
use App\Modules\RiskManagement\Resources\Dashboard\IssuesAndRemediationResource;
use App\Modules\RiskManagement\Resources\Dashboard\RiskAcceptanceSplitResource;

class DashboardController extends Controller
{
    /** Cache TTL (seconds) for the two heaviest endpoints. */
    private const HEAVY_CACHE_TTL = 300;

    public function __construct(
        private DashboardMetricsService $metrics
    ) {
    }

    /* --------------------------------------------------------------- *
     *  Analytics API (consumed by dashboard charts)
     * --------------------------------------------------------------- */

    /** Headline KPI counters. Cached 5 minutes (heavy). */
    public function kpis(Request $request)
    {
        $filters = $request->only(['department', 'framework', 'risk_type', 'owner']);
        $data = Cache::remember(
            'dashboard.kpis.' . md5(json_encode($filters)),
            self::HEAVY_CACHE_TTL,
            fn () => $this->metrics->setFilters($filters)->kpis()
        );

        return new KpiResource($data);
    }

    /** Risk heatmap cells. Cached 5 minutes (heavy). */
    public function heatmap(Request $request)
    {
        $filters = $request->only(['department', 'framework', 'risk_type', 'owner']);
        $data = Cache::remember(
            'dashboard.heatmap.' . md5(json_encode($filters)),
            self::HEAVY_CACHE_TTL,
            fn () => $this->metrics->setFilters($filters)->heatmap()
        );

        return HeatmapCellResource::collection($data);
    }

    /** Highest-risk open findings. */
    public function topRisks(Request $request)
    {
        $filters = $request->only(['department', 'framework', 'risk_type', 'owner']);
        return TopRiskResource::collection($this->metrics->setFilters($filters)->topRisks());
    }

    /** Inherent vs residual risk per domain. */
    public function inherentVsResidualByDept(Request $request)
    {
        $filters = $request->only(['department', 'framework', 'risk_type', 'owner']);
        return InherentVsResidualResource::collection($this->metrics->setFilters($filters)->inherentVsResidualByDept());
    }

    /** Effective / partial / ineffective control split. */
    public function controlEffectiveness(Request $request)
    {
        $filters = $request->only(['department', 'framework', 'risk_type', 'owner']);
        return new ControlEffectivenessResource($this->metrics->setFilters($filters)->controlEffectiveness());
    }

    /** Per-framework compliance percentage AND lifecycle phase. */
    public function complianceScorecard(Request $request)
    {
        $filters = $request->only(['department', 'framework', 'risk_type', 'owner']);
        return ComplianceScorecardResource::collection($this->metrics->setFilters($filters)->complianceScorecard());
    }

    /** Maturity composite + four dimension scores. */
    public function maturityScore(Request $request)
    {
        $filters = $request->only(['department', 'framework', 'risk_type', 'owner']);
        return new MaturityScoreResource($this->metrics->setFilters($filters)->maturityScore());
    }

    /** Open finding count and weighted risk score per domain. */
    public function riskByDepartment(Request $request)
    {
        $filters = $request->only(['department', 'framework', 'risk_type', 'owner']);
        return RiskByDepartmentResource::collection($this->metrics->setFilters($filters)->riskByDepartment());
    }

    /** Issue / remediation status breakdown. */
    public function issuesAndRemediation(Request $request)
    {
        $filters = $request->only(['department', 'framework', 'risk_type', 'owner']);
        return new IssuesAndRemediationResource($this->metrics->setFilters($filters)->issuesAndRemediation());
    }

    /** Accepted / mitigated / open risk-treatment split. */
    public function riskAcceptanceSplit(Request $request)
    {
        $filters = $request->only(['department', 'framework', 'risk_type', 'owner']);
        return new RiskAcceptanceSplitResource($this->metrics->setFilters($filters)->riskAcceptanceSplit());
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();
        $stats = [
            'active_projects' => 0,
            'completed_requirements' => 0,
            'pending_requirements' => 0,
            'meetings' => 0,
        ];

        if ($user->hasRole('Admin')) {
            $stats['active_projects'] = Project::count();
            // Admins can see all upcoming meetings
            $stats['meetings'] = \App\Models\Meeting::where('scheduled_at', '>=', now())->count();
        } else {
            $projects = $user->assignedProjects()->get();
            $stats['active_projects'] = $projects->count();
            
            // Calculate upcoming meetings where the user is either the creator or an attendee
            $stats['meetings'] = \App\Models\Meeting::where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhereHas('users', function ($uq) use ($user) {
                          $uq->where('users.id', $user->id);
                      });
                })
                ->where('scheduled_at', '>=', now())
                ->count();
                
            $completed = 0;
            $pending = 0;
            foreach ($projects as $project) {
                if ($project->module_type === 'pci_dss') {
                    if ($project->pciDssDetails) {
                        $completed += $project->pciDssDetails->findings()->where('is_compliant', true)->count();
                        $pending += $project->pciDssDetails->findings()->where('is_compliant', false)->count();
                    }
                } else {
                    // For agnostic frameworks, we sum findings of the active Gap assessment
                    $gap = $project->gapAssessment;
                    if ($gap) {
                        $completed += $gap->findings()->where('is_compliant', true)->count();
                        $pending += $gap->findings()->where('is_compliant', false)->count();
                    }
                }
            }
            $stats['completed_requirements'] = $completed;
            $stats['pending_requirements'] = $pending;
        }

        return view('dashboard', compact('stats'));
    }

    /**
     * Show the Executive Dashboard.
     */
    public function executive()
    {
        return view('dashboard.executive');
    }

    public function submitComplianceData(Request $request)
    {
        // For demonstration, let's assume we are updating the PCI DSS details for a specific project.
        // In a real application, you would get the project ID from the route, session, or request.
        $projectId = 1; // Replace with actual project ID retrieval logic

        $pciDssDetail = ProjectPciDssDetail::firstOrCreate(
            ['project_id' => $projectId],
            [
                // Initialize with default values if creating a new record
                'ae_company_name' => null,
                // ... other fields ...
            ]
        );

        // Update main attributes
        $pciDssDetail->fill($request->only([
            'overall_assessment_result',
            'summary_findings',
            'business_overview_desc',
            'payment_channels',
            'scope_validation_activities',
            'scope_excluded_areas',
            'scope_reduction_factors',
            'saq_eligibility',
            'segmentation_used',
            'segmentation_desc',
            'pci_ssc_products_used',
            'network_diagrams_desc',
            'account_dataflow_diagrams_desc',
            'storage_account_data_desc',
            'remote_assessment',
            'remote_justification',
            'additional_services',
            'additional_services_desc',
            'subcontractors_used',
            'subcontractor_list',
        ]));

        // Handle JSON fields
        $pciDssDetail->assessment_activities = $request->input('assessment_activities', []);
        $pciDssDetail->overall_findings = $request->input('overall_findings', []);

        $pciDssDetail->save();

        // Handle related models (one-to-many relationships)

        // PCI SSC Products
        $pciDssDetail->pciSscProducts()->delete();
        if ($request->has('products')) {
            foreach ($request->input('products') as $productData) {
                $pciDssDetail->pciSscProducts()->create($productData);
            }
        }

        // Third-Party Service Providers (TPSPs)
        $pciDssDetail->tpsps()->delete();
        if ($request->has('tpsps')) {
            foreach ($request->input('tpsps') as $tpspData) {
                $pciDssDetail->tpsps()->create($tpspData);
            }
        }

        // Networks
        $pciDssDetail->networks()->delete();
        if ($request->has('networks')) {
            foreach ($request->input('networks') as $networkData) {
                $pciDssDetail->networks()->create($networkData);
            }
        }

        // Locations
        $pciDssDetail->locations()->delete();
        if ($request->has('locations')) {
            foreach ($request->input('locations') as $locationData) {
                $pciDssDetail->locations()->create($locationData);
            }
        }

        // Components
        $pciDssDetail->components()->delete();
        if ($request->has('components')) {
            foreach ($request->input('components') as $componentData) {
                $pciDssDetail->components()->create($componentData);
            }
        }

        // External Scans
        $pciDssDetail->externalScans()->delete();
        if ($request->has('external_scans')) {
            foreach ($request->input('external_scans') as $scanData) {
                $pciDssDetail->externalScans()->create($scanData);
            }
        }

        // Internal Scans
        $pciDssDetail->internalScans()->delete();
        if ($request->has('internal_scans')) {
            foreach ($request->input('internal_scans') as $scanData) {
                $pciDssDetail->internalScans()->create($scanData);
            }
        }

        // PCI DSS Findings (from requirements-list.blade.php)
        if ($request->has('findings')) {
            foreach ($request->input('findings') as $requirementId => $findingData) {
                $pciDssDetail->findings()->updateOrCreate(
                    ['pci_dss_requirement_id' => $requirementId],
                    $findingData
                );
            }
        }

        return redirect()->back()->with('success', 'Compliance data saved successfully!');
    }
}
