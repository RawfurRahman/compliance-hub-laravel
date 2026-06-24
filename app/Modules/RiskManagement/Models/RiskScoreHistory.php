<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskScoreHistory extends Model
{
    use HasFactory;

    protected $table = 'risk_scores_history';

    public $timestamps = false;

    protected $fillable = [
        'risk_register_id',
        'tv_score',
        'lh_score',
        'rating_score',
        'threat_level_t',
        'vulnerability_level_av',
        'control_effectiveness',
        'formula_version',
        'residual_tv',
        'residual_lh',
        'residual_rating',
        'recorded_by',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'control_effectiveness' => 'decimal:2',
    ];

    public function risk()
    {
        return $this->belongsTo(RiskRegister::class, 'risk_register_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
