<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GapControl extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'department_id',
        'control_id',
        'requirement_description',
        'required_evidence',
        'status',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function evidenceFiles()
    {
        return $this->belongsToMany(EvidenceFile::class, 'gap_evidence_links', 'gap_control_id', 'evidence_file_id')->withTimestamps();
    }
}
