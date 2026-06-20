<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PciGapAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'requirement_text',
        'is_section_header',
        'status',
        'na_explanation',
        'milestone_date',
        'comments',
    ];

    protected $casts = [
        'is_section_header' => 'boolean',
        'milestone_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
