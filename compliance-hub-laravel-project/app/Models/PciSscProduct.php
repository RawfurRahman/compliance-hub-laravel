<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PciSscProduct extends Model
{
    use HasFactory;

    protected $table = 'pci_ssc_products';

    protected $fillable = [
        'project_pci_dss_detail_id',
        'product_name',
        'version',
        'vendor',
        'description',
    ];

    public function pciDssDetail()
    {
        return $this->belongsTo(ProjectPciDssDetail::class, 'project_pci_dss_detail_id');
    }
}
