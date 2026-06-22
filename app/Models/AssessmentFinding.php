<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\AssessmentService;

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
        'is_compliant',
        'cloned_from_finding_id',
    ];

    protected $casts = [
        'is_compliant' => 'boolean',
    ];

    public function projectAssessment()
    {
        return $this->belongsTo(ProjectAssessment::class, 'project_assessment_id');
    }

    public function frameworkControl()
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
