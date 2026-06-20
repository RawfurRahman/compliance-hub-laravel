<?php

namespace App\View\Components\Pci;

use App\Models\ProjectPciDssDetail;
use Illuminate\View\Component;

class BusinessOverview extends Component
{
    public $details;
    public $paymentChannels;

    public function __construct(?ProjectPciDssDetail $details)
    {
        $this->details = $details;
        $this->paymentChannels = config('compliance.pci_dss.payment_channels', []);
    }

    public function render()
    {
        return view('components.pci.business-overview');
    }
}
