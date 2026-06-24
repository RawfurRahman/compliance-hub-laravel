<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskTreatment extends Model
{
    use SoftDeletes;

    protected $table = 'risk_treatments';
    protected $fillable = [
        'risk_register_id', 'treatment_type', 'description', 'status',
        'progress', 'start_date', 'end_date', 'responsible_person',
        'responsible_department', 'estimated_cost', 'actual_cost', 'notes', 'created_by',
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'progress'   => 'integer',
        'estimated_cost' => 'decimal:2',
        'actual_cost'    => 'decimal:2',
    ];

    public function risk() { return $this->belongsTo(RiskRegister::class, 'risk_register_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function getIsOverdueAttribute(): bool
    {
        return $this->end_date && $this->end_date->isPast() && $this->status !== 'Completed';
    }
}
