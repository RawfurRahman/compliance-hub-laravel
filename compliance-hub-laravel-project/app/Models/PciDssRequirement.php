<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PciDssRequirement extends Model
{
    use HasFactory;

    protected $table = 'pci_dss_requirements';
    protected $guarded = [];

    protected $casts = [
        'testing_procedures' => 'array',
    ];

    // ** NEW RELATIONSHIP **
    public function evidenceFiles()
    {
        return $this->hasMany(EvidenceFile::class, 'pci_dss_requirement_id');
    }
}
