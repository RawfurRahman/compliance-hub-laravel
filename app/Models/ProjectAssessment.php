<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'type',
        'framework',
        'start_date',
        'end_date',
        'cloned_from_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function findings()
    {
        return $this->hasMany(AssessmentFinding::class)->orderBy('serial_no');
    }

    public function clonedFrom()
    {
        return $this->belongsTo(ProjectAssessment::class, 'cloned_from_id');
    }

    // -------------------------------------------------------------------------
    // Computed helpers
    // -------------------------------------------------------------------------

    /**
     * Build the full statistics array used by both the dashboard and report.
     */
    public function stats(): array
    {
        $findings = $this->findings;
        $total    = $findings->count();

        $compliant   = $findings->where('compliance_status', 'Compliant')->count();
        $partial     = $findings->where('compliance_status', 'Partially Compliant')->count();
        $nonCompliant= $findings->where('compliance_status', 'Non-Compliant')->count();
        $na          = $findings->where('compliance_status', 'Not Applicable')->count();

        $high   = $findings->where('risk_rating', 'High')->count();
        $medium = $findings->where('risk_rating', 'Medium')->count();
        $low    = $findings->where('risk_rating', 'Low')->count();

        $open       = $findings->where('status', 'Open')->count();
        $inProgress = $findings->where('status', 'In Progress')->count();
        $closed     = $findings->where('status', 'Closed')->count();

        // Gantt progress: Open=0%, In Progress=50%, Closed=100%
        $progressScore = $total > 0
            ? round((($inProgress * 0.5) + ($closed * 1.0)) / $total * 100, 1)
            : 0;

        // Overall compliance % = Compliant + 0.5 * Partial (excluding N/A from denominator)
        $denominator = $total - $na;
        $compliancePct = $denominator > 0
            ? round((($compliant + ($partial * 0.5)) / $denominator) * 100, 1)
            : 0;

        return compact(
            'total', 'compliant', 'partial', 'nonCompliant', 'na',
            'high', 'medium', 'low',
            'open', 'inProgress', 'closed',
            'progressScore', 'compliancePct'
        );
    }

    /**
     * Build Gantt task array for Frappe Gantt.
     */
    public function ganttTasks(): array
    {
        if (!$this->start_date || !$this->end_date) {
            return [];
        }

        $findings = $this->findings;
        $tasks    = [];

        // Project-level bar
        $tasks[] = [
            'id'       => 'project',
            'name'     => 'Assessment Period',
            'start'    => $this->start_date->format('Y-m-d'),
            'end'      => $this->end_date->format('Y-m-d'),
            'progress' => $this->stats()['progressScore'],
            'custom_class' => 'bar-project',
        ];

        // One bar per finding
        foreach ($findings as $f) {
            $progress = match ($f->status) {
                'Closed'      => 100,
                'In Progress' => 50,
                default       => 0,
            };
            $tasks[] = [
                'id'           => 'f-' . $f->id,
                'name'         => $f->serial_no . ' ' . \Illuminate\Support\Str::limit($f->observation_title, 40),
                'start'        => $this->start_date->format('Y-m-d'),
                'end'          => $this->end_date->format('Y-m-d'),
                'progress'     => $progress,
                'dependencies' => 'project',
                'custom_class' => match ($f->risk_rating) {
                    'High'   => 'bar-high',
                    'Medium' => 'bar-medium',
                    default  => 'bar-low',
                },
            ];
        }

        return $tasks;
    }
}
