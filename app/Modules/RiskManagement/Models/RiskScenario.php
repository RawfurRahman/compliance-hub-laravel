<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskScenario extends Model
{
    use SoftDeletes;

    protected $table = 'risk_scenarios';

    protected $fillable = [
        'risk_register_id', 'title', 'description', 'threat_source',
        'threat_event', 'vulnerability_factor', 'scenario_date', 'created_by',
    ];

    protected $casts = [
        'scenario_date' => 'date',
    ];

    public function risk()
    {
        return $this->belongsTo(RiskRegister::class, 'risk_register_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notes()
    {
        return $this->morphMany(RiskNote::class, 'notable');
    }
}
