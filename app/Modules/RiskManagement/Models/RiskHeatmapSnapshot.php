<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class RiskHeatmapSnapshot extends Model
{
    protected $table = 'risk_heatmap_snapshots';
    public $timestamps = false;
    protected $fillable = [
        'project_id', 'snapshot_type', 'matrix_data',
        'total_risks', 'critical_count', 'high_count', 'medium_count', 'low_count', 'snapped_at',
    ];
    protected $casts = ['matrix_data' => 'array', 'snapped_at' => 'datetime'];

    public function project() { return $this->belongsTo(Project::class); }
}
