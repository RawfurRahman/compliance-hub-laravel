<?php

namespace App\View\Components\Pci;

use App\Models\ProjectPciDssDetail;
use App\Models\PciDssRequirement;
use Illuminate\View\Component;

class RequirementsList extends Component
{
    public $details;
    public $requirements;
    public $findings;

    /**
     * Create a new component instance.
     * This component now fetches its own data to be more self-contained and robust.
     */
    public function __construct(?ProjectPciDssDetail $details)
    {
        $this->details = $details;

        // Fetch all requirements and apply a natural sort to ensure correct order (e.g., 2.1 before 10.1).
        $this->requirements = PciDssRequirement::all()->sortBy('req_num', SORT_NATURAL);

        // Fetch findings only if details are available for the project.
        $this->findings = $details ? $details->findings->keyBy('pci_dss_requirement_id') : collect();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.pci.requirements-list');
    }
}
