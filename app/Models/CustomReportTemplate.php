<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomReportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'report_type',
        'sections',
        'filters',
    ];

    protected $casts = [
        'sections' => 'array',
        'filters' => 'array',
    ];

    /**
     * Get the project that owns this template configuration.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
