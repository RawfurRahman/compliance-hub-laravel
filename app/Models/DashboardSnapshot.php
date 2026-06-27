<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardSnapshot extends Model
{
    protected $table = 'dashboard_snapshots';

    protected $fillable = [
        'domain',
        'business_unit',
        'framework',
        'date_scope',
        'snapshot_date',
        'snapshot_data',
        'metadata',
        'snapped_at',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'snapshot_data' => 'array',
        'metadata' => 'array',
        'snapped_at' => 'datetime',
    ];

    public const DOMAINS = [
        'kpi',
        'heatmap',
        'risk_ranking',
        'compliance_scorecard',
        'third_party_risk',
        'policy_metrics',
        'ownership_sla',
        'remediation_trend',
    ];

    public const DATE_SCOPES = ['daily', 'weekly', 'monthly'];
}
