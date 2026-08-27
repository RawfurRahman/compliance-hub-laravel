<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observation extends Model
{
    public const STATUSES = ['Open', 'In Progress', 'Resolved', 'Closed'];

    protected $fillable = [
        'project_id',
        'assessment_finding_id',
        'final_assessment_finding_id',
        'risk_register_id',
        'title',
        'description',
        'gap',
        'risk_impact',
        'recommendation',
        'management_response',
        'corrective_action',
        'owner_user_id',
        'raised_by',
        'target_date',
        'status',
        'sent_to_final_assessment_at',
    ];

    protected $casts = [
        'target_date' => 'date',
        'sent_to_final_assessment_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function finding()
    {
        return $this->belongsTo(AssessmentFinding::class, 'assessment_finding_id');
    }

    public function finalAssessmentFinding()
    {
        return $this->belongsTo(AssessmentFinding::class, 'final_assessment_finding_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function raisedBy()
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function riskRegister()
    {
        return $this->belongsTo(\App\Modules\RiskManagement\Models\RiskRegister::class, 'risk_register_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->target_date
            && $this->target_date->isPast()
            && ! in_array($this->status, ['Resolved', 'Closed']);
    }
}
