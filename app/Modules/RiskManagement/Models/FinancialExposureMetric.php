<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Persisted financial exposure metric snapshot (portfolio or category rollup).
 */
class FinancialExposureMetric extends Model
{
    use HasFactory;

    protected $table = 'financial_exposure_metrics';

    protected $fillable = [
        'risk_register_id',
        'ale_value',
        'created_at',
    ];

    protected $casts = [
        'ale_value' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}