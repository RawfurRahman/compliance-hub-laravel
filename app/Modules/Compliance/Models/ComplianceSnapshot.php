<?php

namespace App\Modules\Compliance\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceSnapshot extends Model
{
    use HasFactory;

    protected $table = 'comp_compliance_snapshots';

    protected $fillable = [
        'project_id', 'snapshot_type', 'snapshot_data',
        'total_controls', 'compliant_count', 'partial_count',
        'non_compliant_count', 'waived_count', 'overdue_count',
        'under_review_count', 'avg_remediation_time', 'snapshot_date',
    ];

    protected $casts = [
        'snapshot_data' => 'json',
        'snapshot_date' => 'date',
        'avg_remediation_time' => 'decimal:2',
        'total_controls' => 'integer',
        'compliant_count' => 'integer',
        'partial_count' => 'integer',
        'non_compliant_count' => 'integer',
        'waived_count' => 'integer',
        'overdue_count' => 'integer',
        'under_review_count' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('snapshot_type', $type);
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
