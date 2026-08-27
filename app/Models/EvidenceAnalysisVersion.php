<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceAnalysisVersion extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'evidence_file_id',
        'version_number',
        'trigger_type',
        'triggered_by',
        'reason',
        'ai_observations',
        'ai_recommendations',
        'ai_gaps',
        'ai_analysis_status',
        'ai_parse_fallback',
    ];

    protected $casts = [
        'ai_gaps' => 'array',
        'ai_parse_fallback' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function evidenceFile()
    {
        return $this->belongsTo(EvidenceFile::class);
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
