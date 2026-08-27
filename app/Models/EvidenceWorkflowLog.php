<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceWorkflowLog extends Model
{
    protected $fillable = [
        'evidence_file_id',
        'from_status',
        'to_status',
        'user_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function evidenceFile()
    {
        return $this->belongsTo(EvidenceFile::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
