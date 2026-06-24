<?php

namespace App\Modules\Governance\Models;

use App\Models\User;
use App\Modules\Governance\Database\Factories\PolicyVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyVersion extends Model
{
    use HasFactory;

    protected static function newFactory(): PolicyVersionFactory
    {
        return PolicyVersionFactory::new();
    }

    protected $fillable = [
        'policy_id',
        'version_number',
        'title',
        'content',
        'change_summary',
        'status',
        'effective_date',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expires_at' => 'date',
        'version_number' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $version) {
            if (empty($version->created_by) && auth()->check()) {
                $version->created_by = auth()->id();
            }
        });
    }

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviews()
    {
        return $this->hasMany(PolicyReview::class);
    }

    public function approvals()
    {
        return $this->hasMany(PolicyApproval::class);
    }
}
