<?php

namespace App\Reports\Generators;

use App\Models\PciDssRequirement;
use App\Models\Project;
use Illuminate\View\View;

class PciDssRocGenerator extends ReportGenerator
{
    public function generate(Project $project, string $type, array $options = []): View
    {
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
        $requirements = PciDssRequirement::all()->sortBy('req_num', SORT_NATURAL);

        // Get the project's findings, keyed by the requirement ID for easy lookup
        $findings = optional($project->pciDssDetails)->findings->keyBy('pci_dss_requirement_id') ?? collect();

        // Get the list of payment channels from the configuration
        $paymentChannels = config('compliance.pci_dss.payment_channels', []);

        // Calculate compliance metrics for the report
        $totalRequirements = $findings->count();
        $passed = $findings->where('assessment_finding', 'In Place')->count();
        $failed = $findings->where('assessment_finding', 'Not in Place')->count();
        $notTested = $findings->where('assessment_finding', 'Not Tested')->count();
        $notApplicable = $findings->where('assessment_finding', 'Not Applicable')->count();

        $complianceMetrics = [
            'total_requirements' => $totalRequirements,
            'passed' => $passed,
            'failed' => $failed,
            'not_tested' => $notTested,
            'not_applicable' => $notApplicable,
            'compliance_percentage' => $totalRequirements > 0 ? round(($passed / $totalRequirements) * 100, 2) : 0,
            'is_compliant' => $failed === 0 && $notTested === 0,
        ];

        // Return the dedicated report view with all the necessary data
        return view('pci.report', compact('project', 'requirements', 'findings', 'paymentChannels', 'complianceMetrics'));
    }
}
