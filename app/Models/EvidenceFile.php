<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvidenceFile extends Model
{
    use HasFactory;

    // Allow mass assignment for these fields.
    // Ensure 'file_path', 'original_filename', 'mime_type' are also fillable if not using guarded = []
    protected $fillable = [
        'pci_dss_requirement_id',
        'framework_control_id',
        'project_id',
        'user_id',
        'file_path',
        'original_filename',
        'mime_type',
        'trust_center_id',
        'is_publicly_listed',
        'scan_status',
        'scan_details',
        'ai_observations',
        'ai_recommendations',
        'ai_gaps',
        'ai_analysis_status',
        'ai_analysis_approved_by',
        'ai_analysis_approved_at',
        'hitl_status',
        'customer_response',
    ];

    protected $casts = [
        'scan_details' => 'array',
        'ai_observations' => 'string',
        'ai_recommendations' => 'string',
        'ai_gaps' => 'array',
        'ai_analysis_approved_at' => 'datetime',
        'is_publicly_listed' => 'boolean',
    ];

    /**
     * Get the user that owns the evidence file.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the PCI DSS requirement that this evidence file is for.
     */
    public function requirement()
    {
        return $this->belongsTo(PciDssRequirement::class, 'pci_dss_requirement_id');
    }

    public function frameworkControl()
    {
        return $this->belongsTo(FrameworkControl::class, 'framework_control_id');
    }
    
    /**
     * Get the project that the evidence file belongs to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the auditor who approved the AI analysis.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'ai_analysis_approved_by');
    }

    /**
     * Get the communication thread / feedbacks for this evidence file.
     */
    public function feedbacks()
    {
        return $this->hasMany(EvidenceFeedback::class);
    }

    public function trustCenter()
    {
        return $this->belongsTo(\App\Modules\TrustCenter\Models\TrustCenter::class);
    }

    public function scopeAccepted($query)
    {
        return $query->where('hitl_status', 'accepted')
            ->where('ai_analysis_status', 'approved');
    }
}

