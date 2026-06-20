<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'report_type',
        'framework_slug',
        'framework_version',
        'generated_by',
        'generated_at',
        'exported_formats',
        'status',
        'metadata',
    ];

    protected $casts = [
        'exported_formats' => 'array',
        'metadata' => 'array',
        'generated_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
