<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\FrameworkControl;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskRegisterEntry extends Model
{
    use HasFactory;

    protected $table = 'risk_register_entries';

    /** Risk category options */
    public const CATEGORIES = [
        'Cybersecurity',
        'Physical Security',
        'Compliance & Regulatory',
        'Operational',
        'Strategic',
        'Financial',
        'Third-Party / Supply Chain',
        'Data Privacy',
    ];

    /** Common department values */
    public const DEPARTMENTS = [
        'IT Department',
        'Compliance Department',
        'Finance Department',
        'HR Department',
        'Operations',
        'Facilities Department',
        'Legal',
        'Executive',
    ];

    protected $fillable = [
        'project_id',
        'framework_control_id',
        'risk_id',
        'risk_owner',
        'department',
        'date_identified',
        'asset_id',
        'risk_category',
        'risk_description',
        'inherent_likelihood',
        'inherent_impact',
        'inherent_score',
        'inherent_risk_level',
        'recommended_control',
        'treatment_decision',
        'treatment_description',
        'treatment_status',
        'treatment_start_date',
        'treatment_end_date',
        'treatment_progress',
        'residual_likelihood',
        'residual_impact',
        'residual_score',
        'residual_risk_level',
        'notes',
    ];

    protected $casts = [
        'date_identified'      => 'date',
        'treatment_start_date' => 'date',
        'treatment_end_date'   => 'date',
        'inherent_likelihood'  => 'integer',
        'inherent_impact'      => 'integer',
        'inherent_score'       => 'integer',
        'residual_likelihood'  => 'integer',
        'residual_impact'      => 'integer',
        'residual_score'       => 'integer',
        'treatment_progress'   => 'integer',
    ];

    /* ------------------------------------------------------------------ *
     *  Relationships
     * ------------------------------------------------------------------ */

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function frameworkControl()
    {
        return $this->belongsTo(FrameworkControl::class);
    }

    /* ------------------------------------------------------------------ *
     *  Accessors
     * ------------------------------------------------------------------ */

    /** CSS colour-class for the inherent risk badge / heatmap cell */
    public function getInherentColorClassAttribute(): string
    {
        return self::levelToColorClass($this->inherent_risk_level);
    }

    /** CSS colour-class for the residual risk badge / heatmap cell */
    public function getResidualColorClassAttribute(): string
    {
        return self::levelToColorClass($this->residual_risk_level);
    }

    /* ------------------------------------------------------------------ *
     *  Static helpers
     * ------------------------------------------------------------------ */

    /**
     * Derive risk level label from a numeric score (1–25).
     *
     * The 5×5 legend from the uploaded image:
     *   Critical ≥ 20   (red)
     *   High     12–19  (orange/salmon)
     *   Medium    6–11  (yellow)
     *   Low        1–5  (light-green)
     */
    public static function scoreToLevel(int $score): string
    {
        if ($score >= 20) return 'Critical';
        if ($score >= 12) return 'High';
        if ($score >= 6)  return 'Medium';
        return 'Low';
    }

    /** Tailwind / CSS class strings for risk level colouring. */
    public static function levelToColorClass(string $level): string
    {
        return match ($level) {
            'Critical' => 'risk-cell-critical',
            'High'     => 'risk-cell-high',
            'Medium'   => 'risk-cell-medium',
            'Low'      => 'risk-cell-low',
            default    => 'risk-cell-low',
        };
    }

    /**
     * Likelihood label map (1–5).
     */
    public static function likelihoodLabel(int $value): string
    {
        return match ($value) {
            5 => 'Very High',
            4 => 'High',
            3 => 'Medium',
            2 => 'Low',
            1 => 'Very Low',
            default => 'Unknown',
        };
    }

    /**
     * Impact label map (1–5).
     */
    public static function impactLabel(int $value): string
    {
        return match ($value) {
            5 => 'Critical',
            4 => 'High',
            3 => 'Medium',
            2 => 'Low',
            1 => 'Very Low',
            default => 'Unknown',
        };
    }

    /* ------------------------------------------------------------------ *
     *  Query Scopes
     * ------------------------------------------------------------------ */

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeCritical($query)
    {
        return $query->where('inherent_risk_level', 'Critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('inherent_risk_level', 'High');
    }
}
