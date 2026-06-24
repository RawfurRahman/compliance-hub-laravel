<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RiskExposure extends Model
{
    protected $table = 'risk_exposures';

    protected $fillable = [
        'risk_register_id', 'exposure_type', 'inherent_exposure', 'residual_exposure',
        'financial_amount', 'probability_pct', 'impact_rating', 'currency',
        'calculated_at', 'created_by',
    ];

    protected $casts = [
        'inherent_exposure' => 'decimal:2',
        'residual_exposure' => 'decimal:2',
        'financial_amount'  => 'decimal:2',
        'probability_pct'   => 'decimal:2',
        'impact_rating'     => 'integer',
        'calculated_at'     => 'datetime',
    ];

    public function risk()
    {
        return $this->belongsTo(RiskRegister::class, 'risk_register_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getExposureLevelAttribute(): string
    {
        $val = $this->residual_exposure ?? $this->inherent_exposure ?? 0;
        if ($val >= 1000000) return 'Critical';
        if ($val >= 500000)  return 'High';
        if ($val >= 100000)  return 'Medium';
        return 'Low';
    }

    public function notes()
    {
        return $this->morphMany(RiskNote::class, 'notable');
    }
}
