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
        'scan_status', // New: e.g., 'pending', 'clean', 'infected', 'failed'
        'scan_details', // New: JSON for virus scan report
        'ai_observations', // New: AI-generated observations
        'ai_recommendations', // New: AI-generated recommendations
        'ai_analysis_status', // New: e.g., 'pending', 'completed', 'failed', 'awaiting_review'
        'ai_analysis_approved_by', // New: User ID of auditor who approved
        'ai_analysis_approved_at', // New: Timestamp of approval
        'hitl_status', // pending_review, accepted, action_required
        'customer_response', // Customer's response text upon re-uploading or answering
    ];

    // Cast new fields to appropriate types
    protected $casts = [
        'scan_details' => 'array',
        'ai_observations' => 'string', // Store as text
        'ai_recommendations' => 'string', // Store as text
        'ai_analysis_approved_at' => 'datetime',
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
}

