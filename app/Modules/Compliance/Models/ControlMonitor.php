<?php

namespace App\Modules\Compliance\Models;

use App\Models\AssessmentFinding;
use App\Models\Control;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ControlMonitor extends Model
{
    use HasFactory;

    protected $table = 'comp_control_monitors';

    protected $fillable = [
        'control_id', 'monitoring_rule_id', 'last_finding_id',
        'last_run_at', 'next_run_at', 'status',
        'last_result', 'consecutive_failures',
    ];

    protected $casts = [
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'consecutive_failures' => 'integer',
    ];

    public function control()
    {
        return $this->belongsTo(Control::class, 'control_id');
    }

    public function monitoringRule()
    {
        return $this->belongsTo(MonitoringRule::class, 'monitoring_rule_id');
    }

    public function lastFinding()
    {
        return $this->belongsTo(AssessmentFinding::class, 'last_finding_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDue($query)
    {
        return $query->where('next_run_at', '<=', now())->where('status', 'active');
    }
}
