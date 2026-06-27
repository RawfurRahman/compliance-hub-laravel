<?php

namespace App\Modules\RiskManagement\Models;

use Illuminate\Database\Eloquent\Model;

class VendorQuestionnaireResponse extends Model
{
    protected $table = 'vendor_questionnaire_responses';

    protected $fillable = [
        'vendor_assessment_id', 'section', 'question_key', 'question_text',
        'response_text', 'response_type', 'score', 'max_score',
        'evidence_file', 'is_compliant', 'comments',
        'ai_suggested_answer', 'needs_vendor_review',
    ];

    protected $casts = [
        'score'               => 'decimal:2',
        'max_score'           => 'decimal:2',
        'is_compliant'        => 'boolean',
        'needs_vendor_review' => 'boolean',
    ];

    public function assessment()
    {
        return $this->belongsTo(VendorAssessment::class, 'vendor_assessment_id');
    }
}
