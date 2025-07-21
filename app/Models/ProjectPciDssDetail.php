<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PciTpsp;
use App\Models\PciNetwork;
use App\Models\PciLocation;
use App\Models\PciComponent;
use App\Models\PciExternalScan;
use App\Models\PciInternalScan;
use App\Models\PciDssFinding;

class ProjectPciDssDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_pci_dss_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = []; // Allow mass assignment for all fields for simplicity

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'summary_findings' => 'array',
        'payment_channels' => 'array',
        'assessment_activities' => 'array',
        'overall_findings' => 'array',
    ];

    /**
     * Get the parent project that owns these details.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the PCI SSC validated products for the project.
     */
    public function pciSscProducts()
    {
        return $this->hasMany(PciSscProduct::class, 'project_pci_dss_detail_id');
    }

    public function tpsps()
    {
        return $this->hasMany(PciTpsp::class, 'project_pci_dss_detail_id');
    }

    public function networks()
    {
        return $this->hasMany(PciNetwork::class, 'project_pci_dss_detail_id');
    }

    public function locations()
    {
        return $this->hasMany(PciLocation::class, 'project_pci_dss_detail_id');
    }

    public function components()
    {
        return $this->hasMany(PciComponent::class, 'project_pci_dss_detail_id');
    }

    public function externalScans()
    {
        return $this->hasMany(PciExternalScan::class, 'project_pci_dss_detail_id');
    }

    public function internalScans()
    {
        return $this->hasMany(PciInternalScan::class, 'project_pci_dss_detail_id');
    }

    public function findings()
    {
        return $this->hasMany(PciDssFinding::class, 'project_pci_dss_detail_id');
    }
}
