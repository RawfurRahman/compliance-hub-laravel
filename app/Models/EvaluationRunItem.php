<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationRunItem extends Model
{
    protected $fillable = [
        'run_key',
        'item_order',
        'evaluation_corpus_item_id',
        'evidence_file_id',
        'framework_id',
        'framework_control_id',
        'chapter',
        'control_id',
        'evidence_type',
        'evidence_name',
        'ground_truth',
        'predicted_verdict',
        'verdict_match',
        'scan_ms',
        'analysis_ms',
        'total_ms',
        'scan_status',
        'ai_analysis_status',
        'gaps_count',
    ];

    protected $casts = [
        'verdict_match' => 'boolean',
    ];

    public function corpusItem(): BelongsTo
    {
        return $this->belongsTo(EvaluationCorpusItem::class, 'evaluation_corpus_item_id');
    }

    public function evidenceFile(): BelongsTo
    {
        return $this->belongsTo(EvidenceFile::class, 'evidence_file_id');
    }

    public function framework(): BelongsTo
    {
        return $this->belongsTo(Framework::class, 'framework_id');
    }

    public function frameworkControl(): BelongsTo
    {
        return $this->belongsTo(FrameworkControl::class, 'framework_control_id');
    }
}
