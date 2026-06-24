<?php

namespace App\Modules\Governance\Models;

use App\Models\User;
use App\Modules\Governance\Database\Factories\DomainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Domain extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): DomainFactory
    {
        return DomainFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $domain) {
            if (empty($domain->slug)) {
                $domain->slug = Str::slug($domain->name);
            }
        });
    }

    public function policies()
    {
        return $this->hasMany(Policy::class);
    }

    public function metricSnapshots()
    {
        return $this->hasMany(GovernanceMetricSnapshot::class);
    }
}
