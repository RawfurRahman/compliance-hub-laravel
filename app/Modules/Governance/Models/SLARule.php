<?php

namespace App\Modules\Governance\Models;

use App\Models\User;
use App\Modules\Governance\Database\Factories\SLARuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SLARule extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): SLARuleFactory
    {
        return SLARuleFactory::new();
    }

    protected $table = 'sla_rules';

    protected $fillable = [
        'policy_id',
        'name',
        'description',
        'trigger_event',
        'action_type',
        'sla_hours',
        'escalation_interval_hours',
        'escalation_user_id',
        'is_active',
    ];

    protected $casts = [
        'sla_hours' => 'integer',
        'escalation_interval_hours' => 'integer',
        'is_active' => 'boolean',
    ];

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    public function escalationUser()
    {
        return $this->belongsTo(User::class, 'escalation_user_id');
    }
}
