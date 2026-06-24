<?php

namespace App\Modules\Compliance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SLATracker extends Model
{
    use HasFactory;

    protected $table = 'comp_sla_trackers';

    protected $fillable = [
        'trackable_type', 'trackable_id', 'sla_type',
        'deadline_at', 'breached_at', 'breach_notified', 'status',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
        'breached_at' => 'datetime',
        'breach_notified' => 'boolean',
    ];

    public function trackable()
    {
        return $this->morphTo();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBreached($query)
    {
        return $query->where('status', 'breached');
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline_at', '<', now())
            ->where('status', 'active');
    }

    public function getIsBreachedAttribute(): bool
    {
        return $this->status === 'breached';
    }
}
