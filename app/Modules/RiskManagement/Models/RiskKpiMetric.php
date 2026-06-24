<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class RiskKpiMetric extends Model
{
    protected $table = 'risk_kpi_metrics';
    protected $fillable = [
        'project_id', 'kpi_name', 'category',
        'target_value', 'actual_value', 'variance',
        'unit', 'rag_status', 'period_start', 'period_end', 'notes',
    ];
    protected $casts = [
        'period_start'  => 'date',
        'period_end'    => 'date',
        'target_value'  => 'decimal:2',
        'actual_value'  => 'decimal:2',
        'variance'      => 'decimal:2',
    ];

    public function project() { return $this->belongsTo(Project::class); }
}
