<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class RiskSnapshot extends Model
{
    protected $table = 'risk_snapshots';

    protected $fillable = [
        'project_id', 'snapshot_type', 'snapshot_data',
        'total_risks', 'critical_count', 'high_count', 'medium_count', 'low_count',
        'total_exposure', 'avg_inherent_score', 'avg_residual_score',
        'metadata', 'snapped_at',
    ];

    protected $casts = [
        'snapshot_data'      => 'array',
        'metadata'           => 'array',
        'snapped_at'         => 'datetime',
        'total_exposure'     => 'decimal:2',
        'avg_inherent_score' => 'decimal:2',
        'avg_residual_score' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeLatestByType($query, string $type)
    {
        return $query->where('snapshot_type', $type)->latest('snapped_at');
    }
}
