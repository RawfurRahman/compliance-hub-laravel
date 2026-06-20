<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectPciDssDetail;
use App\Models\PciDssRequirement;
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
        $project->load('pciDssDetails.pciSscProducts', 'pciDssDetails.tpsps', 'pciDssDetails.networks', 'pciDssDetails.locations', 'pciDssDetails.components', 'pciDssDetails.externalScans', 'pciDssDetails.internalScans', 'pciDssDetails.findings.requirement', 'evidence.user', 'chatMessages.user.roles');
        
        // Get payment channels from the config file
        $paymentChannels = config('compliance.pci_dss.payment_channels', []);

        // Fetch all PCI DSS requirements and sort them naturally
        $requirements = PciDssRequirement::all()->sortBy('req_num', SORT_NATURAL);

        // Group evidence files by requirement ID for easy access in the view
        $evidenceByRequirement = $project->evidence->groupBy('pci_dss_requirement_id');

        // Get chat messages for the project
        $chatMessages = $project->chatMessages;

        // Extract findings from pciDssDetails and key them by requirement ID
        $findings = optional($project->pciDssDetails)->findings->keyBy('pci_dss_requirement_id') ?? collect();

        // Pass all necessary data to the view
        return view('pci.show', compact('project', 'paymentChannels', 'requirements', 'evidenceByRequirement', 'chatMessages', 'findings'));
    }

    /**
     * Update the specified PCI DSS project in storage.
     */
    public function update(Request $request, Project $project)
    {
        if ($project->module_type !== 'pci_dss') {
            abort(404);
        }

        $request->validate($this->validationRules());

        $pciDssDetail = $project->pciDssDetails;
        $detailsData = $this->getDetailsDataFromRequest($request);
        $pciDssDetail->update($detailsData);
        $this->processAndSaveRelationships($request, $pciDssDetail);

        return redirect()->route('pci.show', $project)->with('success', 'Project information updated successfully!');
    }

    /**
     * Get the validation rules for PCI DSS project details.
     */
    private function validationRules(): array
    {
        return [
            'ae_company_name' => 'nullable|string|max:255',
            'ae_dba' => 'nullable|string|max:255',
            'ae_mailing_address' => 'nullable|string|max:255',
            'ae_main_website' => 'nullable|string|max:255',
            'ae_contact_name' => 'nullable|string|max:255',
            'ae_contact_title' => 'nullable|string|max:255',
            'ae_phone_number' => 'nullable|string|max:255',
            'ae_email_address' => 'nullable|email|max:255',
            'assessor_company_name' => 'nullable|string|max:255',
            'assessor_mailing_address' => 'nullable|string|max:255',
            'assessor_website' => 'nullable|string|max:255',
            'assessor_lead_name' => 'nullable|string|max:255',
            'assessor_phone' => 'nullable|string|max:255',
            'assessor_email' => 'nullable|email|max:255',
            'assessor_certificate_number' => 'nullable|string|max:255',
            'date_of_report' => 'nullable|date',
            'date_assessment_ended' => 'nullable|date',
            'remote_assessment' => 'boolean',
            'remote_justification' => 'nullable|string',
            'additional_services' => 'boolean',
            'additional_services_desc' => 'nullable|string',
            'subcontractors_used' => 'boolean',
            'subcontractor_list' => 'nullable|string',
            'overall_assessment_result' => 'nullable|string',
            'summary_findings' => 'nullable|string',
            'business_overview_desc' => 'nullable|string',
            'payment_channels' => 'nullable|array',
            'scope_validation_activities' => 'nullable|string',
            'scope_excluded_areas' => 'nullable|string',
            'scope_reduction_factors' => 'nullable|string',
            'saq_eligibility' => 'nullable|string',
            'segmentation_used' => 'boolean',
            'segmentation_desc' => 'nullable|string',
            'pci_ssc_products_used' => 'boolean',
            'network_diagrams_desc' => 'nullable|string',
            'account_dataflow_diagrams_desc' => 'nullable|string',
            'storage_account_data_desc' => 'nullable|string',
        ];
    }

    /**
     * Extracts and prepares data for the main project_pci_dss_details table from the request.
     */
    private function getDetailsDataFromRequest(Request $request): array
    {
        $allData = $request->all();
        $relationKeys = ['products', 'tpsps', 'networks', 'locations', 'components', 'ext_scans', 'int_scans', 'findings', '_token', '_method', 'project_name'];
        $detailsData = collect($allData)->except($relationKeys)->toArray();

        // Ensure boolean values are correctly cast from the request.
        $detailsData['remote_assessment'] = $request->boolean('remote_assessment');
        $detailsData['additional_services'] = $request->boolean('additional_services');
        $detailsData['subcontractors_used'] = $request->boolean('subcontractors_used');
        $detailsData['segmentation_used'] = $request->boolean('segmentation_used');
        $detailsData['pci_ssc_products_used'] = $request->boolean('pci_ssc_products_used');

        // Convert summary findings from a string to an array if needed.
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
        // Handle PCI SSC Products
        $details->pciSscProducts()->delete();
        if ($request->boolean('pci_ssc_products_used') && $request->has('products')) {
            $details->pciSscProducts()->createMany($request->products);
        }

        // Handle TPSP, Networks, and Locations
        $details->tpsps()->delete();
        if ($request->has('tpsps')) { $details->tpsps()->createMany($request->tpsps); }

        $details->networks()->delete();
        if ($request->has('networks')) { $details->networks()->createMany($request->networks); }
        
        $details->locations()->delete();
        if ($request->has('locations')) { $details->locations()->createMany($request->locations); }

        // Handle Components
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

        // Handle External and Internal Scans
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
        
        // Handle Findings using updateOrCreate for efficiency
        if ($request->has('findings')) {
            foreach ($request->findings as $reqId => $findingData) {
                $details->findings()->updateOrCreate(
                    ['pci_dss_requirement_id' => $reqId],
                    [
                        'assessment_finding' => $findingData['assessment_finding'] ?? null,
                        'compensating_control' => isset($findingData['compensating_control']),
                        'customized_approach' => isset($findingData['customized_approach']),
                        'finding_description' => $findingData['finding_description'] ?? null,
                        'assessor_responses' => $findingData['assessor_responses'] ?? [],
                        'is_applicable' => filter_var($findingData['is_applicable'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'required_documents' => $findingData['required_documents'] ?? null,
                    ]
                );
            }
        }
    }
}
