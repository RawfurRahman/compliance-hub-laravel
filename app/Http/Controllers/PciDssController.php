<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectPciDssDetail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PciDssController extends Controller
{
    /**
     * Display the specified PCI DSS project.
     */
    public function show(Project $project)
    {
        // Ensure the project is a PCI DSS module type
        if ($project->module_type !== 'pci_dss') {
            abort(404);
        }

        // Eager load all related details for efficiency
        $project->load('pciDssDetails.pciSscProducts', 'pciDssDetails.tpsps', 'pciDssDetails.networks', 'pciDssDetails.locations', 'pciDssDetails.components', 'pciDssDetails.externalScans', 'pciDssDetails.internalScans', 'pciDssDetails.findings');
        
        // Get payment channels from the config file
        $paymentChannels = config('compliance.pci_dss.payment_channels', []);

        // The requirements and findings are now loaded by the RequirementsList component directly.
        return view('pci.show', compact('project', 'paymentChannels'));
    }

    /**
     * Store a newly created PCI DSS project's details in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'project_name' => 'required|string|max:255',
            // Add all other validation rules similar to the update method...
        ]);

        $project = Project::create([
            'name' => $validatedData['project_name'],
            'module_type' => 'pci_dss',
            'user_id' => auth()->id(),
        ]);
        
        $pciDssDetail = $project->pciDssDetails()->create(
            $this->getDetailsDataFromRequest($request)
        );

        $this->processAndSaveRelationships($request, $pciDssDetail);

        return redirect()->route('pci.show', $project)->with('success', 'New project information added successfully!');
    }


    /**
     * Update the specified PCI DSS project in storage.
     */
    public function update(Request $request, Project $project)
    {
        if ($project->module_type !== 'pci_dss') {
            abort(404);
        }

        $request->validate([
            'ae_company_name' => 'nullable|string|max:255',
            // Add all other validation rules...
        ]);

        $pciDssDetail = $project->pciDssDetails;
        $detailsData = $this->getDetailsDataFromRequest($request);
        $pciDssDetail->update($detailsData);
        $this->processAndSaveRelationships($request, $pciDssDetail);

        return redirect()->route('pci.show', $project)->with('success', 'Project information updated successfully!');
    }

    /**
     * Extracts and prepares data for the main project_pci_dss_details table from the request.
     */
    private function getDetailsDataFromRequest(Request $request): array
    {
        $allData = $request->all();
        $relationKeys = ['products', 'tpsps', 'networks', 'locations', 'components', 'ext_scans', 'int_scans', 'findings', '_token', '_method', 'project_name'];
        $detailsData = collect($allData)->except($relationKeys)->toArray();

        $detailsData['remote_assessment'] = $request->boolean('remote_assessment');
        $detailsData['additional_services'] = $request->boolean('additional_services');
        $detailsData['subcontractors_used'] = $request->boolean('subcontractors_used');
        $detailsData['segmentation_used'] = $request->boolean('segmentation_used');
        $detailsData['pci_ssc_products_used'] = $request->boolean('pci_ssc_products_used');

        if (isset($detailsData['summary_findings']) && is_string($detailsData['summary_findings'])) {
            $detailsData['summary_findings'] = explode("\n", $detailsData['summary_findings']);
        }

        return $detailsData;
    }

    /**
     * Helper function to process and save relationship data from dynamic tables.
     */
    private function processAndSaveRelationships(Request $request, ProjectPciDssDetail $details)
    {
        // Products
        $details->pciSscProducts()->delete();
        if ($request->boolean('pci_ssc_products_used') && $request->has('products')) {
            $details->pciSscProducts()->createMany($request->products);
        }

        // TPSP, Networks, Locations
        $details->tpsps()->delete();
        if ($request->has('tpsps')) { $details->tpsps()->createMany($request->tpsps); }

        $details->networks()->delete();
        if ($request->has('networks')) { $details->networks()->createMany($request->networks); }
        
        $details->locations()->delete();
        if ($request->has('locations')) { $details->locations()->createMany($request->locations); }

        // Components
        $details->components()->delete();
        if ($request->has('components')) {
            foreach ($request->components as $componentData) {
                if (!empty($componentData['name']) || !empty($componentData['type'])) {
                    $details->components()->create([
                        'name' => $componentData['name'] ?? null,
                        'type' => $componentData['type'] ?? null,
                    ]);
                }
            }
        }

        // Scans
        $details->externalScans()->delete();
        if ($request->has('ext_scans')) {
            foreach ($request->ext_scans as $scan) {
                if (!empty($scan['scan_date']) || !empty($scan['result'])) {
                    $details->externalScans()->create(['scan_date' => $scan['scan_date'],'result' => $scan['result'],'initial_assessment' => isset($scan['initial_assessment']),]);
                }
            }
        }
        $details->internalScans()->delete();
        if ($request->has('int_scans')) {
            foreach ($request->int_scans as $scan) {
                 if (!empty($scan['scan_date']) || !empty($scan['result'])) {
                    $details->internalScans()->create(['scan_date' => $scan['scan_date'],'result' => $scan['result'],'initial_assessment' => isset($scan['initial_assessment']),]);
                }
            }
        }
        
        // Findings
        if ($request->has('findings')) {
            foreach ($request->findings as $reqId => $findingData) {
                $details->findings()->updateOrCreate(['pci_dss_requirement_id' => $reqId],
                    ['assessment_finding' => $findingData['assessment_finding'] ?? null,'compensating_control' => isset($findingData['compensating_control']),'customized_approach' => isset($findingData['customized_approach']),'finding_description' => $findingData['finding_description'] ?? null,'assessor_responses' => $findingData['assessor_responses'] ?? [],]
                );
            }
        }
    }
}
