<?php

namespace App\Modules\Governance\Models;

use App\Models\User;
use App\Modules\Governance\Database\Factories\PolicyWaiverFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PolicyWaiver extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): PolicyWaiverFactory
    {
        return PolicyWaiverFactory::new();
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
        'compensating_controls',
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
