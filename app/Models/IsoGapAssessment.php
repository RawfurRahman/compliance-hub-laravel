<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IsoGapAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'serial_no',
        'clause_reference',
        'observation_title',
        'risk_rating',
        'current_state',
        'gap_description',
        'impact_risk',
        'recommendation',
        'status',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
