<?php

namespace App\Modules\Governance\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class GovernanceMetricSnapshot extends Model
{
    protected $table = 'governance_metric_snapshots';

    protected $fillable = [
        'project_id',
        'domain_id',
        'snapshot_type',
        'total_policies',
        'published_policies',
        'draft_policies',
        'under_review_policies',
        'expired_policies',
        'overdue_reviews',
        'pending_approvals',
        'active_waivers',
        'active_exceptions',
        'sla_breaches',
        'metadata',
        'snapped_at',
    ];

    protected $casts = [
        'metadata' => 'json',
        'snapped_at' => 'datetime',
        'total_policies' => 'integer',
        'published_policies' => 'integer',
        'draft_policies' => 'integer',
        'under_review_policies' => 'integer',
        'expired_policies' => 'integer',
        'overdue_reviews' => 'integer',
        'pending_approvals' => 'integer',
        'active_waivers' => 'integer',
        'active_exceptions' => 'integer',
        'sla_breaches' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
