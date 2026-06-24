<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RiskKriMetric extends Model
{
    protected $table = 'risk_kri_metrics';
    protected $fillable = [
        'risk_register_id', 'kri_name', 'unit',
        'threshold_green', 'threshold_amber', 'threshold_red',
        'current_value', 'rag_status', 'measured_at', 'owner_id',
    ];
    protected $casts = [
        'measured_at'      => 'date',
        'threshold_green'  => 'decimal:2',
        'threshold_amber'  => 'decimal:2',
        'threshold_red'    => 'decimal:2',
        'current_value'    => 'decimal:2',
    ];

    public function risk() { return $this->belongsTo(RiskRegister::class, 'risk_register_id'); }
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
}
