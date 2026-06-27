<?php

namespace App\Modules\TrustCenter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustCenterQuestionnaire extends Model
{
    use HasFactory;

    const QUESTIONS = [
        ['key' => 'security_certifications', 'question' => 'What security certifications does your organization hold (e.g., ISO 27001, SOC 2)?'],
        ['key' => 'data_encryption',         'question' => 'Do you encrypt data at rest and in transit?'],
        ['key' => 'incident_response',       'question' => 'What is your incident response process?'],
        ['key' => 'access_controls',         'question' => 'How do you manage access controls and authentication?'],
        ['key' => 'business_continuity',     'question' => 'Do you have a business continuity and disaster recovery plan?'],
        ['key' => 'data_retention',          'question' => 'What is your data retention and deletion policy?'],
        ['key' => 'penetration_testing',     'question' => 'How often do you conduct penetration testing?'],
        ['key' => 'subprocessors',           'question' => 'Do you use any sub-processors or third-party vendors?'],
        ['key' => 'data_hosting',            'question' => 'Where is your data hosted (data centers / regions)?'],
        ['key' => 'compliance_frameworks',   'question' => 'Which compliance frameworks do you adhere to?'],
    ];

    protected $fillable = [
        'trust_center_id',
        'requester_name',
        'requester_email',
        'requester_company',
        'status',
        'responses',
        'submitted_at',
        'responded_at',
    ];

    protected $casts = [
        'responses'    => 'array',
        'submitted_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function trustCenter(): BelongsTo
    {
        return $this->belongsTo(TrustCenter::class);
    }
}
