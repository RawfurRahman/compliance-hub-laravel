<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * RiskResidualScore
 *
 * Dedicated, append-only history of every residual (after-controls) scoring
 * output. Stored separately from inherent history so the dashboard can plot
 * both as independent series. Each row keeps the formula version and a verbatim
 * input snapshot for exact later reconstruction, plus manual-override audit
 * metadata.
 */
class RiskResidualScore extends Model
{
    use HasFactory;

    protected $table = 'risk_residual_scores';

    public const UPDATED_AT = null; // append-only history; no updates

    protected $fillable = [
        'risk_register_id',
        'inherent_score',
        'residual_score',
        'severity_band',
        'appetite_status',
        'reduction_pct',
        'heatmap_likelihood',
        'heatmap_impact',
        'trend_direction',
        'manual_override',
        'override_reason',
        'formula_version',
        'input_snapshot',
        'explanation',
        'source',
        'recorded_by',
    ];

    protected $casts = [
        'inherent_score'     => 'integer',
        'residual_score'     => 'integer',
        'reduction_pct'      => 'decimal:2',
        'heatmap_likelihood' => 'integer',
        'heatmap_impact'     => 'integer',
        'manual_override'    => 'boolean',
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
