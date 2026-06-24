<?php

namespace App\Modules\Governance\Models;

use App\Models\User;
use App\Modules\Governance\Database\Factories\PolicyExceptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PolicyException extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): PolicyExceptionFactory
    {
        return PolicyExceptionFactory::new();
    }

    protected $fillable = [
        'policy_id',
        'policy_version_id',
        'title',
        'description',
        'justification',
        'requested_by',
        'approved_by',
        'status',
        'effective_date',
        'expires_at',
        'department',
        'risk_acceptance',
        'rejection_reason',
        'metadata',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expires_at' => 'date',
        'metadata' => 'json',
    ];

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    public function policyVersion()
    {
        return $this->belongsTo(PolicyVersion::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
