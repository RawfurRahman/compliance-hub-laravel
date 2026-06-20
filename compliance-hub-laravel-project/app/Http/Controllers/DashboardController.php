<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

class DashboardController extends Controller
{
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
            $stats['meetings'] = 0;
        } else {
            $projects = $user->assignedProjects()->get();
            $stats['active_projects'] = $projects->count();
            
            // Calculate upcoming meetings where the user is either the creator or an attendee
            $stats['meetings'] = 0;
                
            // Mocking logic: later we will sum up completed requirements across frameworks
        }

        return view('dashboard', compact('stats'));
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
