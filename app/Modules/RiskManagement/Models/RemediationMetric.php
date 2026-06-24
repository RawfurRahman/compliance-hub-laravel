<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Persisted remediation performance metric snapshot (MTTR / SLA).
 */
class RemediationMetric extends Model
{
    use HasFactory;

    protected $table = 'remediation_metrics';

    protected $fillable = [
        'project_id', 'scope',
        'total_items', 'open_items', 'closed_items', 'overdue_count',
        'mttr_hours', 'mtta_hours', 'mt_assign_hours', 'mttc_hours',
        'sla_breach_rate', 'closure_rate',
        'aging_buckets', 'breakdown', 'calculated_at',
    ];

    protected $casts = [
        'total_items'      => 'integer',
        'open_items'       => 'integer',
        'closed_items'     => 'integer',
        'overdue_count'    => 'integer',
        'mttr_hours'       => 'decimal:2',
        'mtta_hours'       => 'decimal:2',
        'mt_assign_hours'  => 'decimal:2',
        'mttc_hours'       => 'decimal:2',
        'sla_breach_rate'  => 'decimal:2',
        'closure_rate'     => 'decimal:2',
        'aging_buckets'    => 'array',
        'breakdown'        => 'array',
        'calculated_at'    => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
