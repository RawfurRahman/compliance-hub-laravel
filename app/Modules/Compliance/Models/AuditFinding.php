<?php

namespace App\Modules\Compliance\Models;

use App\Models\Control;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditFinding extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'comp_audit_findings';

    protected $fillable = [
        'project_id', 'finding_reference', 'title', 'description',
        'audit_date', 'auditor_id', 'severity', 'status',
        'control_id', 'framework_control_id', 'remediation_plan', 'due_date',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'due_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function control()
    {
        return $this->belongsTo(Control::class, 'control_id');
    }

    public function frameworkControl()
    {
        return $this->belongsTo(FrameworkControl::class, 'framework_control_id');
    }

    public function slaTrackers()
    {
        return $this->morphMany(SLATracker::class, 'trackable');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_review']);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && !in_array($this->status, ['resolved', 'closed']);
    }
}
