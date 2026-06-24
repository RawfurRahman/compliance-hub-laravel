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
        'project_id', 'scope', 'category',
        'risk_count',
        'single_loss_expectancy', 'annualized_loss_expectancy',
        'expected_remediation_cost', 'business_interruption_impact',
        'portfolio_exposure', 'currency', 'breakdown', 'calculated_at',
    ];

    protected $casts = [
        'risk_count'                   => 'integer',
        'single_loss_expectancy'       => 'decimal:2',
        'annualized_loss_expectancy'   => 'decimal:2',
        'expected_remediation_cost'    => 'decimal:2',
        'business_interruption_impact' => 'decimal:2',
        'portfolio_exposure'           => 'decimal:2',
        'breakdown'                    => 'array',
        'calculated_at'                => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
