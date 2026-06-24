<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\Control;
use App\Models\FrameworkControl;
use App\Models\User;
use App\Modules\RiskManagement\Jobs\RiskRecalculationJob;
use Illuminate\Database\Eloquent\Model;

class RiskControlMapping extends Model
{
    protected $table = 'risk_control_mappings';

    protected $fillable = [
        'risk_register_id',
        'framework_control_id',
        'control_id',
        'effectiveness',
        'control_type',
        'notes',
        'mapping_status',
        'confidence_score',
        'mapped_by',
        'mapped_at',
    ];

    protected $casts = [
        'confidence_score' => 'float',
        'mapped_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saved(function ($mapping) {
            if ($mapping->risk_register_id) {
                RiskRecalculationJob::dispatch($mapping->risk_register_id);
            }
        });

        static::deleted(function ($mapping) {
            if ($mapping->risk_register_id) {
                RiskRecalculationJob::dispatch($mapping->risk_register_id);
            }
        });
    }

    public function risk()
    {
        return $this->belongsTo(RiskRegister::class, 'risk_register_id');
    }

    public function frameworkControl()
    {
        return $this->belongsTo(FrameworkControl::class, 'framework_control_id');
    }

    public function control()
    {
        return $this->belongsTo(Control::class, 'control_id');
    }

    public function mappedBy()
    {
        return $this->belongsTo(User::class, 'mapped_by');
    }
}
