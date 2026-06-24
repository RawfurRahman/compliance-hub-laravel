<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RiskAppetite extends Model
{
    protected $table = 'risk_appetite';
    protected $fillable = [
        'project_id', 'critical_threshold', 'high_threshold', 'medium_threshold',
        'max_financial_exposure', 'target_residual_score', 'appetite_statement',
        'approved_by', 'effective_date',
    ];
    protected $casts = [
        'effective_date'         => 'date',
        'max_financial_exposure' => 'decimal:2',
        'target_residual_score'  => 'decimal:2',
    ];

    public function project() { return $this->belongsTo(Project::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }

    /** Check whether a given score exceeds appetite thresholds */
    public function exceedsAppetite(int $score): bool
    {
        return $score >= $this->high_threshold;
    }
}
