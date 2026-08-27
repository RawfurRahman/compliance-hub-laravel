<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'module_type',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the users (Auditors, Customers) assigned to this project.
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'project_user')->withTimestamps();
    }

    public function pciDssDetails(): HasOne
    {
        return $this->hasOne(ProjectPciDssDetail::class);
    }

    public function evidence()
    {
        return $this->hasMany(Evidence::class);
    }

    public function evidenceFiles()
    {
        return $this->hasMany(EvidenceFile::class);
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function gapControls()
    {
        return $this->hasMany(GapControl::class);
    }

    public function reports()
    {
        return $this->hasMany(GeneratedReport::class);
    }

    public function integrations()
    {
        return $this->hasMany(Integration::class);
    }

    public function pciGapAssessments()
    {
        return $this->hasMany(PciGapAssessment::class);
    }

    public function isoGapAssessments()
    {
        return $this->hasMany(IsoGapAssessment::class);
    }

    public function requiredDocumentLists()
    {
        return $this->hasMany(RequiredDocumentList::class);
    }

    /**
     * Get the active gap assessment (ProjectAssessment with type 'gap').
     */
    public function gapAssessment()
    {
        return $this->hasOne(ProjectAssessment::class)->where('type', 'gap');
    }

    public function projectAssessments()
    {
        return $this->hasMany(ProjectAssessment::class);
    }
}
