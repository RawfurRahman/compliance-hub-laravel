<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaturityScoreSnapshot extends Model
{
    use HasFactory;

    protected $table = 'maturity_score_snapshots';

    public const DIMENSION_RISK_MANAGEMENT     = 'risk_management';
    public const DIMENSION_CONTROL_DESIGN      = 'control_design';
    public const DIMENSION_REMEDIATION_VELOCITY = 'remediation_velocity';
    public const DIMENSION_EVIDENCE_AUDIT      = 'evidence_audit';
    public const DIMENSION_COMPOSITE           = 'composite';

    protected $fillable = [
        'snapshot_date',
        'dimension',
        'score_value',
        'sample_size',
        'calculation_notes',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'score_value'   => 'decimal:1',
        'sample_size'   => 'integer',
    ];
}
