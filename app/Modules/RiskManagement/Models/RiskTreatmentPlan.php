<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskTreatmentPlan extends Model
{
    use SoftDeletes;

    protected $table = 'risk_treatment_plans';

    protected $fillable = [
        'risk_register_id', 'assessment_finding_id', 'title', 'treatment_type', 'description',
        'controls_required', 'responsible_party', 'budget_estimated', 'budget_actual',
        'start_date', 'target_date', 'completion_date', 'status',
        'progress_pct', 'effectiveness_rating', 'notes', 'created_by',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'target_date'     => 'date',
        'completion_date' => 'date',
        'budget_estimated' => 'decimal:2',
        'budget_actual'    => 'decimal:2',
        'progress_pct'     => 'integer',
        'effectiveness_rating' => 'integer',
    ];

    public function risk()
    {
        return $this->belongsTo(RiskRegister::class, 'risk_register_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assessmentFinding()
    {
        return $this->belongsTo(\App\Models\AssessmentFinding::class, 'assessment_finding_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->target_date
            && $this->target_date->isPast()
            && !in_array($this->status, ['completed', 'cancelled']);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['planned', 'in_progress']);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('target_date')
            ->where('target_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function notes()
    {
        return $this->morphMany(RiskNote::class, 'notable');
    }
}
