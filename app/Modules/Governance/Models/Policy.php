<?php

namespace App\Modules\Governance\Models;

use App\Models\User;
use App\Models\Project;
use App\Modules\Governance\Database\Factories\PolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Policy extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): PolicyFactory
    {
        return PolicyFactory::new();
    }

    protected $fillable = [
        'domain_id',
        'title',
        'slug',
        'policy_number',
        'description',
        'status',
        'effective_date',
        'expires_at',
        'owner_user_id',
        'department',
        'business_unit',
        'current_version',
        'is_active',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expires_at' => 'date',
        'published_at' => 'datetime',
        'is_active' => 'boolean',
        'current_version' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $policy) {
            if (empty($policy->slug)) {
                $policy->slug = Str::slug($policy->title) . '-' . Str::random(6);
            }
            if (empty($policy->policy_number)) {
                $prefix = config('governance.policy_number_prefix', 'GOV-POL-');
                $policy->policy_number = $prefix . str_pad((self::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT);
            }
            if (empty($policy->created_by) && auth()->check()) {
                $policy->created_by = auth()->id();
            }
            if (empty($policy->updated_by) && auth()->check()) {
                $policy->updated_by = auth()->id();
            }
        });

        static::updating(function (self $policy) {
            if (auth()->check()) {
                $policy->updated_by = auth()->id();
            }
        });
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function ownerUser()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function versions()
    {
        return $this->hasMany(PolicyVersion::class);
    }

    public function reviews()
    {
        return $this->hasMany(PolicyReview::class);
    }

    public function approvals()
    {
        return $this->hasMany(PolicyApproval::class);
    }

    public function publications()
    {
        return $this->hasMany(PolicyPublication::class);
    }

    public function exceptions()
    {
        return $this->hasMany(PolicyException::class);
    }

    public function waivers()
    {
        return $this->hasMany(PolicyWaiver::class);
    }

    public function ownershipMatrix()
    {
        return $this->hasMany(OwnershipMatrix::class);
    }

    public function stakeholders()
    {
        return $this->hasMany(Stakeholder::class);
    }

    public function slaRules()
    {
        return $this->hasMany(SLARule::class);
    }

    public function currentVersion()
    {
        return $this->belongsTo(PolicyVersion::class, 'current_version', 'version_number')
            ->where('policy_id', $this->id);
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->whereHas('domain', fn ($q) => $q);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = [
            'draft' => ['under_review'],
            'under_review' => ['draft', 'approved'],
            'approved' => ['draft', 'published'],
            'published' => ['deprecated', 'expired'],
            'deprecated' => ['archived'],
            'archived' => ['draft'],
            'expired' => [],
        ];

        return in_array($newStatus, $allowed[$this->status] ?? []);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'archived']);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }
}
