<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvidenceFeedback extends Model
{
    use HasFactory;

    protected $table = 'evidence_feedbacks';

    protected $fillable = [
        'evidence_file_id',
        'user_id',
        'message',
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
