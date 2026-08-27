<?php

namespace App\Models;

use App\Modules\Compliance\Models\ComplianceTest;
use App\Modules\Compliance\Models\ControlEvidence;
use App\Modules\Compliance\Models\ControlMonitor;
use App\Modules\Compliance\Models\ControlTest;
use App\Modules\RiskManagement\Models\RiskControlMapping;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Control extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'controls';

    protected $fillable = [
        'control_code',
        'code',
        'name',
        'title',
        'description',
        'framework_id',
        'is_active',
        'status',
        'effectiveness_score',
        'control_owner_id',
    ];

    public function framework()
    {
        return $this->belongsTo(Framework::class, 'framework_id');
    }

    public function controlOwner()
    {
        return $this->belongsTo(User::class, 'control_owner_id');
    }

    public function riskControlMappings()
    {
        return $this->hasMany(RiskControlMapping::class, 'control_id');
    }

    public function frameworkControls()
    {
        return $this->belongsToMany(FrameworkControl::class, 'comp_framework_control_map', 'control_id', 'framework_control_id')
            ->withPivot(['mapping_type', 'mapping_notes', 'effectiveness_weight'])
            ->withTimestamps();
    }

    public function controlTests()
    {
        return $this->hasMany(ControlTest::class, 'control_id');
    }

    public function controlMonitors()
    {
        return $this->hasMany(ControlMonitor::class, 'control_id');
    }

    public function controlEvidence()
    {
        return $this->hasMany(ControlEvidence::class, 'control_id');
    }

    public function complianceTests()
    {
        return $this->hasMany(ComplianceTest::class, 'control_id');
    }
}
