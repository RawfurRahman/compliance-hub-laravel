<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationCorpusItem extends Model
{
    use HasFactory;

    public const GROUND_TRUTHS = ['compliant', 'partial', 'non_compliant'];

    public const EVIDENCE_TYPES = ['screenshot', 'diagram', 'policy_page', 'config_export', 'log_extract'];

    public const CHAPTERS = ['chapter_6', 'chapter_7'];

    protected $fillable = [
        'framework_control_id',
        'chapter',
        'ground_truth',
        'evidence_type',
        'evidence_name',
        'evidence_summary',
        'truth_rationale',
        'expected_gaps',
    ];

    protected $casts = [
        'expected_gaps' => 'array',
    ];

    public function frameworkControl(): BelongsTo
    {
        return $this->belongsTo(FrameworkControl::class, 'framework_control_id');
    }
}
