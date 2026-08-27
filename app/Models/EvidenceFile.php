<?php

namespace App\Models;

use App\Modules\RiskManagement\Models\RiskRegister;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'pci_dss_requirement_id',
        'framework_control_id',
        'project_id',
        'user_id',
        'file_path',
        'original_filename',
        'mime_type',
        'scan_status',
        'scan_details',
        'ai_observations',
        'ai_recommendations',
        'ai_gaps',
        'ai_analysis_status',
        'ai_analysis_approved_by',
        'ai_analysis_approved_at',
        'ai_gaps_consolidated_at',
        'scan_ms',
        'analysis_ms',
        'total_ms',
        'ai_parse_fallback',
        'hitl_status',
        'customer_response',
        'analysis_report_data',
        'tracker_status',
        'gap_assessment_sent_at',
        'final_report_flagged_at',
        'risk_register_created_at',
        'report_section_data',
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
        'scan_details' => 'array',
        'ai_observations' => 'string',
        'ai_recommendations' => 'string',
        'ai_gaps' => 'array',
        'ai_analysis_approved_at' => 'datetime',
        'ai_gaps_consolidated_at' => 'datetime',
        'analysis_report_data' => 'array',
        'report_section_data' => 'array',
        'gap_assessment_sent_at' => 'datetime',
        'final_report_flagged_at' => 'datetime',
        'risk_register_created_at' => 'datetime',
        'gap_category' => 'string',
        'non_compliant_details' => 'string',
        'compliant_description' => 'string',
        'remediation_plan' => 'string',
        'evidence_provided' => 'string',
        'test_results' => 'string',
        'meets_standard' => 'boolean',
        'auditor_notes' => 'string',
    ];

    public const TRACKER_STATUSES = [
        'pending',
        'awaiting_review',
        'submitted',
        'approved',
        'gap_assessment_sent',
        'final_report_ready',
        'risk_created',
        'rejected',
    ];

    public const TRACKER_TRANSITIONS = [
        'pending' => ['awaiting_review'],
        'awaiting_review' => ['submitted', 'rejected'],
        'submitted' => ['approved', 'rejected'],
        'approved' => ['gap_assessment_sent', 'final_report_ready', 'risk_created'],
        'gap_assessment_sent' => ['final_report_ready', 'risk_created'],
        'final_report_ready' => ['risk_created'],
        'risk_created' => [],
        'rejected' => ['awaiting_review'],
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requirement()
    {
        return $this->belongsTo(PciDssRequirement::class, 'pci_dss_requirement_id');
    }

    public function frameworkControl(): BelongsTo
    {
        return $this->belongsTo(FrameworkControl::class, 'framework_control_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'ai_analysis_approved_by');
    }

    public function feedbacks()
    {
        return $this->hasMany(EvidenceFeedback::class);
    }

    public function consolidatedFindings()
    {
        return $this->hasMany(AssessmentFinding::class, 'source_id', 'id')
            ->where('source_type', 'evidence');
    }

    public function workflowLogs()
    {
        return $this->hasMany(EvidenceWorkflowLog::class);
    }

    public function isoGapAssessments()
    {
        return $this->hasMany(IsoGapAssessment::class);
    }

    public function pciGapAssessments()
    {
        return $this->hasMany(PciGapAssessment::class);
    }

    public function analysisVersions()
    {
        return $this->hasMany(EvidenceAnalysisVersion::class)->orderBy('version_number');
    }

    /**
     * Snapshot the evidence file's current AI-analysis fields into a new
     * immutable version row. Called both after a fresh AI run completes and
     * before a re-analysis overwrites the prior result, so history is never lost.
     */
    public function recordAnalysisVersion(string $triggerType, ?int $triggeredBy = null, ?string $reason = null): EvidenceAnalysisVersion
    {
        $nextVersion = ($this->analysisVersions()->max('version_number') ?? 0) + 1;

        return $this->analysisVersions()->create([
            'version_number' => $nextVersion,
            'trigger_type' => $triggerType,
            'triggered_by' => $triggeredBy,
            'reason' => $reason,
            'ai_observations' => $this->ai_observations,
            'ai_recommendations' => $this->ai_recommendations,
            'ai_gaps' => $this->ai_gaps,
            'ai_analysis_status' => $this->ai_analysis_status,
            'ai_parse_fallback' => $this->ai_parse_fallback,
        ]);
    }

    public function assessmentFinding()
    {
        return $this->hasOne(AssessmentFinding::class, 'source_id', 'id')
            ->where('source_type', 'evidence')
            ->where('framework_control_id', $this->framework_control_id);
    }

    public function riskRegister()
    {
        return $this->hasOne(RiskRegister::class, 'evidence_file_id', 'id')
            ->orWhere(function ($q) {
                $q->where('source', 'evidence_tracker')
                    ->whereHas('assessmentFinding', function ($sq) {
                        $sq->where('source_type', 'evidence')
                            ->where('source_id', $this->id);
                    });
            });
    }

    public function scopeAccepted($query)
    {
        return $query->where('hitl_status', 'accepted')
            ->where('ai_analysis_status', 'approved');
    }

    public function scopeForTracker($query)
    {
        return $query->whereNotNull('framework_control_id');
    }

    public function scopeByTrackerStatus($query, $status)
    {
        return $query->where('tracker_status', $status);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::TRACKER_TRANSITIONS[$this->tracker_status] ?? [];

        return in_array($newStatus, $allowed);
    }
}
