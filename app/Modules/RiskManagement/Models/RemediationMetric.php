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
        'bucket_range',
        'issue_count',
        'created_at',
    ];

    protected $casts = [
        'issue_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}