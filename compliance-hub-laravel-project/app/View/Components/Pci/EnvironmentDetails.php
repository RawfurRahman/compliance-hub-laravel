<?php

namespace App\View\Components\Pci;

use App\Models\ProjectPciDssDetail;
use Illuminate\View\Component;

class EnvironmentDetails extends Component
{
    public $details;

    public function __construct(?ProjectPciDssDetail $details)
    {
        $this->details = $details;
    }

    public function render()
    {
        return view('components.pci.environment-details');
    }
}
