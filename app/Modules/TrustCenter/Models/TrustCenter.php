<?php

namespace App\Modules\TrustCenter\Models;

use App\Models\EvidenceFile;
use App\Models\Framework;
use App\Models\Project;
use App\Models\ProjectAssessment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class TrustCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'public_slug',
        'is_published',
        'headline',
        'summary',
        'contact_email',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->public_slug)) {
                $model->public_slug = Str::random(12);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function accessRequests(): HasMany
    {
        return $this->hasMany(TrustCenterAccessRequest::class);
    }

    public function publicFrameworks()
    {
        return $this->hasManyThrough(
            Framework::class,
            ProjectAssessment::class,
            'project_id',
            'id',
            'project_id',
            'framework_id'
        )->where('project_assessments.is_publicly_visible', true);
    }

    public function publicEvidence()
    {
        return $this->hasMany(EvidenceFile::class)
            ->where('is_publicly_listed', true);
    }

    public function questionnaires(): HasMany
    {
        return $this->hasMany(TrustCenterQuestionnaire::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(TrustCenterVisit::class);
    }
}
