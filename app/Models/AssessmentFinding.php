<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentFinding extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_assessment_id',
        'serial_no',
        'clause_reference',
        'observation_title',
        'compliance_status',
        'risk_rating',
        'current_state',
        'gap_description',
        'impact_risk',
        'recommendation',
        'status',
    ];

    public function assessment()
    {
        return $this->belongsTo(ProjectAssessment::class, 'project_assessment_id');
    }
}
