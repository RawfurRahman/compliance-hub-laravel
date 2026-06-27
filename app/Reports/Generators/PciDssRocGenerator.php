<?php

namespace App\Reports\Generators;

use App\Models\Project;
use Illuminate\View\View;

abstract class ReportGenerator
{
    abstract public function generate(Project $project, string $type, array $options = []): View;
}

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
        $requirements = \App\Models\PciDssRequirement::all()->sortBy('req_num', SORT_NATURAL);

        // Get the project's findings, keyed by the requirement ID for easy lookup
        $findings = optional($project->pciDssDetails)->findings->keyBy('pci_dss_requirement_id') ?? collect();

        // Get the list of payment channels from the configuration
        $paymentChannels = config('compliance.pci_dss.payment_channels', []);

        // Return the dedicated report view with all the necessary data
        return view('pci.report', compact('project', 'requirements', 'findings', 'paymentChannels'));
    }
}