<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\Asset;
use App\Models\Control;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use App\Modules\RiskManagement\Jobs\RiskRecalculationJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskRegister extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'risk_registers';

    protected static function booted()
    {
        static::saved(function ($risk) {
            if ($risk->isDirty(['threat_level_t', 'vulnerability_level_av', 'likelihood_lh'])) {
                \App\Modules\RiskManagement\Jobs\RiskRecalculationJob::dispatch($risk->id);
            }
        });
    }

    public const LIKELIHOOD_LABELS = [
        1 => 'Very Unlikely',
        2 => 'Unlikely',
        3 => 'Possible',
        4 => 'Likely',
        5 => 'Frequent',
    ];

    // Axis labels for 5×5 heat map (likelihood rows in descending order)
    public const LIKELIHOOD_AXIS = [
        5 => 'Very High',
        4 => 'High',
        3 => 'Medium',
        2 => 'Low',
        1 => 'Very Low',
    ];

    public const IMPACT_AXIS = [
        1 => 'Very Low',
        2 => 'Low',
        3 => 'Medium',
        4 => 'High',
        5 => 'Critical',
    ];

    // For frontend dropdowns
    public const STATUSES = [
        'Not Started', 'Pending', 'In Progress', 'Completed'
    ];

    public const TREATMENT_DECISIONS = [
        'Accepted', 'Not Accepted'
    ];

    public const LIFECYCLE_TRANSITIONS = [
        'draft'      => ['assessed'],
        'assessed'   => ['accepted', 'treated', 'monitoring'],
        'accepted'   => ['treated', 'monitoring'],
        'treated'    => ['monitoring', 'assessed'],
        'monitoring' => ['escalated', 'closed', 'assessed'],
        'escalated'  => ['treated', 'accepted', 'monitoring'],
        'closed'     => ['monitoring', 'assessed'],
        'expired'    => ['assessed'],
    ];

    public const LIFECYCLE_STATUSES = [
        'draft', 'assessed', 'accepted', 'treated',
        'monitoring', 'escalated', 'closed', 'expired',
    ];

    protected $fillable = [
        'project_id', 'framework_control_id',
        'serial_no', 'asset_process_service', 'risk_owner', 'risk_calculation_date',
        'asset_value_bdt', 'threats', 'threat_level_t', 'vulnerabilities',
        'impact_confidentiality', 'impact_integrity', 'impact_availability',
        'existing_control', 'vulnerability_level_av', 'tv_t_av', 'likelihood_lh',
        'risk_rating_avtvlh', 'measurement', 'proposed_control', 'communication',
        'implementation_from', 'implementation_to', 'implementation_status',
        'lifecycle_status',
        'residual_tv', 'residual_lh', 'residual_rating', 'follow_up_note',
        'category', 'department',
        'owner_user_id', 'asset_id', 'evidence_ids', 'source', 'legacy_source_id', 'assessment_finding_id',
        'created_by', 'updated_by', 'custom_fields',
        'computed_tv', 'computed_risk_rating', 'computed_residual_rating',
        'exposure_value',
    ];

    protected $casts = [
        'risk_calculation_date' => 'date',
        'implementation_from' => 'date',
        'implementation_to' => 'date',
        'threats' => 'json',
        'vulnerabilities' => 'json',
        'evidence_ids' => 'json',
        'custom_fields' => 'json',
        'asset_value_bdt' => 'decimal:2',
        'threat_level_t' => 'integer',
        'vulnerability_level_av' => 'integer',
        'tv_t_av' => 'integer',
        'likelihood_lh' => 'integer',
        'risk_rating_avtvlh' => 'integer',
        'impact_confidentiality' => 'integer',
        'impact_integrity' => 'integer',
        'impact_availability' => 'integer',
        'residual_tv' => 'integer',
        'residual_lh' => 'integer',
        'residual_rating' => 'integer',
        'computed_tv' => 'integer',
        'computed_risk_rating' => 'integer',
        'computed_residual_rating' => 'integer',
        'exposure_value' => 'decimal:2',
    ];

    // Ensure serializing to JSON includes old field names for the frontend views
    protected $appends = [
        'risk_id',
        'risk_name',
        'date_identified',
        'asset_id_ref',
        'threat_score',
        'confidentiality',
        'integrity',
        'availability',
        'existing_controls',
        'likelihood',
        'inherent_score',
        'inherent_risk_level',
        'recommended_control',
        'treatment_decision',
        'status',
        'residual_likelihood',
        'residual_impact',
        'residual_score',
        'residual_risk_level',
        'follow_up_notes'
    ];

    /* ------------------------------------------------------------------ */
    /* Backward Compatibility Accessors & Mutators                        */
    /* ------------------------------------------------------------------ */

    public function getRiskIdAttribute() { return $this->serial_no; }
    public function setRiskIdAttribute($value) { $this->serial_no = $value; }

    public function getRiskNameAttribute() { return $this->asset_process_service; }
    public function setRiskNameAttribute($value) { $this->asset_process_service = $value; }

    public function getDateIdentifiedAttribute() { return $this->risk_calculation_date; }
    public function setDateIdentifiedAttribute($value) { $this->risk_calculation_date = $value; }

    public function getAssetIdRefAttribute() { return $this->asset_id; }
    public function setAssetIdRefAttribute($value) { $this->asset_id = $value; }

    public function getThreatScoreAttribute() { return $this->threat_level_t; }
    public function setThreatScoreAttribute($value) { $this->threat_level_t = $value; }

    public function getConfidentialityAttribute() { return $this->impact_confidentiality; }
    public function setConfidentialityAttribute($value) { $this->impact_confidentiality = $value; }

    public function getIntegrityAttribute() { return $this->impact_integrity; }
    public function setIntegrityAttribute($value) { $this->impact_integrity = $value; }

    public function getAvailabilityAttribute() { return $this->impact_availability; }
    public function setAvailabilityAttribute($value) { $this->impact_availability = $value; }

    public function getExistingControlsAttribute() { return $this->existing_control; }
    public function setExistingControlsAttribute($value) { $this->existing_control = $value; }

    public function getLikelihoodAttribute() { return $this->likelihood_lh; }
    public function setLikelihoodAttribute($value) { $this->likelihood_lh = $value; }

    public function getInherentScoreAttribute() { return $this->risk_rating_avtvlh; }
    public function setInherentScoreAttribute($value) { $this->risk_rating_avtvlh = $value; }

    public function getRecommendedControlAttribute() { return $this->proposed_control; }
    public function setRecommendedControlAttribute($value) { $this->proposed_control = $value; }

    public function getTreatmentDecisionAttribute() { return $this->measurement; }
    public function setTreatmentDecisionAttribute($value) { $this->measurement = $value; }

    public function getStatusAttribute() { return $this->implementation_status; }
    public function setStatusAttribute($value) { $this->implementation_status = $value; }

    public function getResidualLikelihoodAttribute() { return $this->residual_lh; }
    public function setResidualLikelihoodAttribute($value) { $this->residual_lh = $value; }

    public function getResidualImpactAttribute() { return $this->residual_tv; }
    public function setResidualImpactAttribute($value) { $this->residual_tv = $value; }

    public function getResidualScoreAttribute() { return $this->residual_rating; }
    public function setResidualScoreAttribute($value) { $this->residual_rating = $value; }

    public function getFollowUpNotesAttribute() { return $this->follow_up_note; }
    public function setFollowUpNotesAttribute($value) { $this->follow_up_note = $value; }

    /* ------------------------------------------------------------------ */
    /* Relationships                                                        */
    /* ------------------------------------------------------------------ */

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function frameworkControl()
    {
        return $this->belongsTo(FrameworkControl::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function ownerUser()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assessmentFinding()
    {
        return $this->belongsTo(\App\Models\AssessmentFinding::class, 'assessment_finding_id');
    }

    public function controlMappings()
    {
        return $this->hasMany(RiskControlMapping::class, 'risk_register_id');
    }

    public function mappedFrameworkControls()
    {
        return $this->belongsToMany(FrameworkControl::class, 'risk_control_mappings', 'risk_register_id', 'framework_control_id')
            ->withPivot(['mapping_status', 'confidence_score', 'effectiveness', 'control_type', 'notes'])
            ->withTimestamps();
    }

    public function mappedControls()
    {
        return $this->belongsToMany(Control::class, 'risk_control_mappings', 'risk_register_id', 'control_id')
            ->withPivot(['mapping_status', 'confidence_score', 'effectiveness', 'control_type', 'notes'])
            ->withTimestamps();
    }

    public function scoresHistory()
    {
        return $this->hasMany(RiskScoreHistory::class, 'risk_register_id');
    }

    public function comments()
    {
        return $this->hasMany(RiskComment::class, 'risk_register_id');
    }

    public function acceptances()
    {
        return $this->hasMany(RiskAcceptance::class, 'risk_register_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'risk_register_tags', 'risk_register_id', 'tag_id');
    }

    public function scenarios()
    {
        return $this->hasMany(RiskScenario::class, 'risk_register_id');
    }

    public function treatmentPlans()
    {
        return $this->hasMany(RiskTreatmentPlan::class, 'risk_register_id');
    }

    public function exposures()
    {
        return $this->hasMany(RiskExposure::class, 'risk_register_id');
    }

    public function latestAcceptance()
    {
        return $this->hasOne(RiskAcceptance::class, 'risk_register_id')->latestOfMany();
    }

    public function reviews()
    {
        return $this->hasMany(RiskReview::class, 'risk_register_id');
    }

    public function latestReview()
    {
        return $this->hasOne(RiskReview::class, 'risk_register_id')->latestOfMany();
    }

    public function notes()
    {
        return $this->morphMany(RiskNote::class, 'notable');
    }

    /* ------------------------------------------------------------------ */
    /* Static helpers & Scopes                                              */
    /* ------------------------------------------------------------------ */

    public static function scoreToLevel(int $score): string
    {
        if ($score >= 128) return 'Critical';
        if ($score >= 84)  return 'High';
        if ($score >= 54)  return 'Medium';
        return 'Low';
    }

    public static function levelToColorClass(string $level): string
    {
        return match ($level) {
            'Critical' => 'risk-critical',
            'High'     => 'risk-high',
            'Medium'   => 'risk-medium',
            'Low'      => 'risk-low',
            default    => 'risk-low',
        };
    }

    public static function levelToBgStyle(string $level): string
    {
        return match ($level) {
            'Critical' => 'background:#c0392b;color:#fff;',
            'High'     => 'background:#e67e22;color:#fff;',
            'Medium'   => 'background:#f1c40f;color:#333;',
            'Low'      => 'background:#2ecc71;color:#fff;',
            default    => 'background:#ecf0f1;color:#333;',
        };
    }

    public function getInherentRiskLevelAttribute(): string
    {
        return self::scoreToLevel($this->risk_rating_avtvlh);
    }

    public function getResidualRiskLevelAttribute(): string
    {
        return self::scoreToLevel($this->residual_rating);
    }

    public function getLikelihoodLabelAttribute(): string
    {
        return self::LIKELIHOOD_LABELS[$this->likelihood_lh] ?? 'Unknown';
    }

    public function getResidualLikelihoodLabelAttribute(): string
    {
        return self::LIKELIHOOD_LABELS[$this->residual_lh] ?? 'Unknown';
    }

    public function getRiskReductionPctAttribute(): float
    {
        if ($this->risk_rating_avtvlh <= 0) return 0;
        return round((1 - ($this->residual_rating / $this->risk_rating_avtvlh)) * 100, 1);
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::LIFECYCLE_TRANSITIONS[$this->lifecycle_status] ?? []);
    }

    public function getDeltaAttribute(): int
    {
        $inherent = $this->computed_risk_rating ?? $this->risk_rating_avtvlh;
        $residual = $this->computed_residual_rating ?? $this->residual_rating;
        return max(0, $inherent - $residual);
    }
}
