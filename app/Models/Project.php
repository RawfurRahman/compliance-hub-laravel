<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function pciDssDetails()
    {
        return $this->hasOne(ProjectPciDssDetail::class);
    }

    public function scope()
    {
        return $this->hasOne(ProjectScope::class);
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

    public function isoGapAssessments()
    {
        return $this->hasMany(IsoGapAssessment::class);
    }

    public function pciGapAssessments()
    {
        return $this->hasMany(PciGapAssessment::class);
    }

    public function assessments()
    {
        return $this->hasMany(ProjectAssessment::class);
    }

    public function gapAssessment()
    {
        return $this->hasOne(ProjectAssessment::class)->where('type', 'Gap');
    }

    public function finalAssessment()
    {
        return $this->hasOne(ProjectAssessment::class)->where('type', 'Final');
    }

    public function requiredDocumentLists()
    {
        return $this->hasMany(RequiredDocumentList::class);
    }

    public function reports()
    {
        return $this->hasMany(GeneratedReport::class);
    }

    public function reportSchedules()
    {
        return $this->hasMany(ReportSchedule::class);
    }

    public function customReportTemplates()
    {
        return $this->hasMany(CustomReportTemplate::class);
    }
}


