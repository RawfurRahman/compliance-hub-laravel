<?php

namespace App\Modules\Governance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PolicyPublication extends Model
{
    protected $fillable = [
        'policy_id',
        'policy_version_id',
        'published_by',
        'method',
        'audience',
        'notification_sent',
        'metadata',
        'published_at',
    ];

    protected $casts = [
        'notification_sent' => 'boolean',
        'metadata' => 'json',
        'published_at' => 'datetime',
    ];

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    public function policyVersion()
    {
        return $this->belongsTo(PolicyVersion::class);
    }

    public function publishedBy()
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
