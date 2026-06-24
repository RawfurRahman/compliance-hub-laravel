<?php

namespace App\Modules\Governance\Models;

use App\Models\User;
use App\Modules\Governance\Database\Factories\PolicyReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyReview extends Model
{
    use HasFactory;

    protected static function newFactory(): PolicyReviewFactory
    {
        return PolicyReviewFactory::new();
    }

    protected $fillable = [
        'policy_id',
        'policy_version_id',
        'reviewer_user_id',
        'review_type',
        'comments',
        'status',
        'due_date',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $review) {
            if (empty($review->created_by) && auth()->check()) {
                $review->created_by = auth()->id();
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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
