<?php

namespace App\Modules\Governance\Models;

use App\Models\User;
use App\Modules\Governance\Database\Factories\PolicyApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyApproval extends Model
{
    use HasFactory;

    protected static function newFactory(): PolicyApprovalFactory
    {
        return PolicyApprovalFactory::new();
    }

    protected $fillable = [
        'policy_id',
        'policy_version_id',
        'approver_user_id',
        'approval_type',
        'status',
        'comments',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'created_by',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $approval) {
            if (empty($approval->created_by) && auth()->check()) {
                $approval->created_by = auth()->id();
            }
        });
    }

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    public function policyVersion()
    {
        return $this->belongsTo(PolicyVersion::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
