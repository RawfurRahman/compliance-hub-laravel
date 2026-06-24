<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * RiskInherentScore
 *
 * Dedicated, append-only history of every inherent (before-controls) scoring
 * output. Each row stores the formula version and a verbatim snapshot of the
 * inputs so the calculation can be reproduced exactly at any later time, even
 * if the business rules change.
 */
class RiskInherentScore extends Model
{
    use HasFactory;

    protected $table = 'risk_inherent_scores';

    public const UPDATED_AT = null; // append-only history; no updates

    protected $fillable = [
        'risk_register_id',
        'tv_score',
        'inherent_score',
        'severity_band',
        'appetite_status',
        'heatmap_likelihood',
        'heatmap_impact',
        'risk_ranking',
        'formula_version',
        'input_snapshot',
        'explanation',
        'source',
        'recorded_by',
    ];

    protected $casts = [
        'tv_score'           => 'integer',
        'inherent_score'     => 'integer',
        'heatmap_likelihood' => 'integer',
        'heatmap_impact'     => 'integer',
        'risk_ranking'       => 'decimal:2',
        'input_snapshot'     => 'array',
        'explanation'        => 'array',
        'created_at'         => 'datetime',
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
