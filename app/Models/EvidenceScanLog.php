<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceScanLog extends Model
{
    protected $fillable = [
        'evidence_file_id',
        'project_id',
        'user_id',
        'original_filename',
        'file_path',
        'scan_status',
        'virus_name',
        'scan_details',
        'quarantined',
        'quarantine_path',
        'scanned_at',
    ];

    protected $casts = [
        'scan_details' => 'array',
        'quarantined' => 'boolean',
        'scanned_at' => 'datetime',
    ];

    public function evidenceFile(): BelongsTo
    {
        return $this->belongsTo(EvidenceFile::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
