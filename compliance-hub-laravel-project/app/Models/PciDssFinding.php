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
        'is_applicable' => 'boolean',
    ];

    public function requirement()
    {
        return $this->belongsTo(PciDssRequirement::class, 'pci_dss_requirement_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
