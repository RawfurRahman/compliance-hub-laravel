<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorAssessment extends Model
{
    use SoftDeletes;

    protected $table = 'vendor_assessments';

    protected $fillable = [
        'vendor_id', 'assessor_id', 'assessment_type', 'assessment_date',
        'due_date', 'completed_date', 'status', 'overall_score',
        'risk_rating', 'findings_summary', 'remediation_required',
        'remediation_deadline', 'notes',
    ];

    protected $casts = [
        'assessment_date'      => 'date',
        'due_date'             => 'date',
        'completed_date'       => 'date',
        'remediation_deadline' => 'date',
        'overall_score'        => 'decimal:2',
        'remediation_required' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(ThirdPartyVendor::class, 'vendor_id');
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function responses()
    {
        return $this->hasMany(VendorQuestionnaireResponse::class, 'vendor_assessment_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && !in_array($this->status, ['completed', 'failed']);
    }

    public function scoreToRating(?float $score): ?string
    {
        if ($score === null) return null;
        if ($score >= 90) return 'Low';
        if ($score >= 70) return 'Medium';
        if ($score >= 50) return 'High';
        return 'Critical';
    }

    public function notes()
    {
        return $this->morphMany(RiskNote::class, 'notable');
    }
}
