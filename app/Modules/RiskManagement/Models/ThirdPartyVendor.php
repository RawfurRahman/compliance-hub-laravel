<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThirdPartyVendor extends Model
{
    use SoftDeletes;

    protected $table = 'third_party_vendors';

    protected $fillable = [
        'project_id', 'vendor_name', 'vendor_code', 'contact_name',
        'contact_email', 'contact_phone', 'website', 'service_category',
        'criticality', 'risk_tier', 'contract_start', 'contract_end',
        'data_classification', 'data_shared', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'contract_start' => 'date',
        'contract_end'   => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assessments()
    {
        return $this->hasMany(VendorAssessment::class, 'vendor_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCritical($query)
    {
        return $query->where('criticality', 'critical');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function notes()
    {
        return $this->morphMany(RiskNote::class, 'notable');
    }
}
