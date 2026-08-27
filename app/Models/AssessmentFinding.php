<?php

namespace App\Models;

use App\Modules\Compliance\Models\ControlTest;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Services\AssessmentService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentFinding extends Model
{
    use HasFactory;

    protected $table = 'assessment_findings';

    protected $fillable = [
        'project_assessment_id',
        'framework_control_id',
        'status',
        'risk_rating',
        'observation',
        'gap_description',
        'impact',
        'recommendation',
        'due_date',
        'is_compliant',
        'is_applicable',
        'cloned_from_finding_id',
        'risk_register_id',
        'source_type',
        'source_id',
        'compliance_state',
        'ai_gaps',
        'ai_gaps_consolidated_at',
        'ai_gaps_consolidated_by',
        'gap_category',
        'non_compliant_details',
        'compliant_description',
        'remediation_plan',
        'evidence_provided',
        'test_results',
        'meets_standard',
        'auditor_notes',
    ];

    protected $casts = [
        'is_compliant' => 'boolean',
        'is_applicable' => 'boolean',
        'due_date' => 'date',
        'ai_gaps' => 'array',
        'ai_gaps_consolidated_at' => 'datetime',
        'meets_standard' => 'boolean',
    ];

    public function projectAssessment(): BelongsTo
    {
        return $this->belongsTo(ProjectAssessment::class, 'project_assessment_id');
    }

    public function frameworkControl(): BelongsTo
    {
        return $this->belongsTo(FrameworkControl::class, 'framework_control_id');
    }

    public function clonedFrom()
    {
        return $this->belongsTo(AssessmentFinding::class, 'cloned_from_finding_id');
    }

    public function clones()
    {
        return $this->hasMany(AssessmentFinding::class, 'cloned_from_finding_id');
    }

    public function evidence()
    {
        return $this->belongsToMany(Evidence::class, 'assessment_finding_evidence', 'assessment_finding_id', 'evidence_id');
    }

    public function getSerialNoAttribute()
    {
        return $this->frameworkControl ? $this->frameworkControl->control_id : '';
    }

    public function getObservationTitleAttribute()
    {
        return $this->observation;
    }

    public function getImpactRiskAttribute()
    {
        return $this->impact;
    }

    public function getCurrentStateAttribute()
    {
        return $this->observation;
    }

    public function getStandardReferenceAttribute()
    {
        return $this->frameworkControl ? $this->frameworkControl->required_evidence : '';
    }

    public function riskRegister()
    {
        return $this->belongsTo(RiskRegister::class, 'risk_register_id');
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function consolidatedBy()
    {
        return $this->belongsTo(User::class, 'ai_gaps_consolidated_by');
    }

    public function controlTests()
    {
        return $this->hasMany(ControlTest::class, 'assessment_finding_id');
    }

    public function observations()
    {
        return $this->hasMany(\App\Models\Observation::class, 'assessment_finding_id');
    }

    protected static function booted()
    {
        static::saved(function ($finding) {
            // Delegate the sync logic to AssessmentService
            app(AssessmentService::class)->syncFinding($finding);
        });

        static::deleted(function ($finding) {
            // Delete cloned findings when the parent Gap finding is deleted
            app(AssessmentService::class)->deleteClonedFinding($finding);
        });
    }
}
