<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PciDssFinding extends Model
{
    use HasFactory;

    protected $table = 'pci_dss_findings';
    protected $guarded = [];

    protected $casts = [
        'assessor_responses' => 'array',
    ];

    public function requirement()
    {
        return $this->belongsTo(PciDssRequirement::class, 'pci_dss_requirement_id');
    }
}
