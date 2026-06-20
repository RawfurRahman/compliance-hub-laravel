<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'framework',
        'assessment_type',
        'start_date',
        'end_date',
        'cloned_from_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function findings()
    {
        return $this->hasMany(AssessmentFinding::class, 'assessment_id')->orderBy('serial_no');
    }

    public function clonedFrom()
    {
        return $this->belongsTo(Assessment::class, 'cloned_from_id');
    }

    public function stats(): array
    {
        $findings = $this->findings;
        $total    = $findings->count();

        $compliant    = $findings->where('is_compliant', true)->count();
        $nonCompliant = $findings->where('is_compliant', false)->count();

        $high   = $findings->where('risk_rating', 'High')->count();
        $medium = $findings->where('risk_rating', 'Medium')->count();
        $low    = $findings->where('risk_rating', 'Low')->count();
        $none   = $findings->where('risk_rating', 'None')->count();

        $open       = $findings->where('status', 'Open')->count();
        $inProgress = $findings->where('status', 'In Progress')->count();
        $closed     = $findings->where('status', 'Closed')->count();

        $progressScore = $total > 0
            ? round((($inProgress * 0.5) + ($closed * 1.0)) / $total * 100, 1)
            : 0;

        $compliancePct = $total > 0
            ? round(($compliant / $total) * 100, 1)
            : 0;

        return compact(
            'total', 'compliant', 'nonCompliant',
            'high', 'medium', 'low', 'none',
            'open', 'inProgress', 'closed',
            'progressScore', 'compliancePct'
        );
    }

    public function ganttTasks(): array
    {
        if (!$this->start_date || !$this->end_date) {
            return [];
        }

        $findings = $this->findings;
        $tasks    = [];

        $tasks[] = [
            'id'       => 'project',
            'name'     => 'Assessment Period',
            'start'    => $this->start_date->format('Y-m-d'),
            'end'      => $this->end_date->format('Y-m-d'),
            'progress' => $this->stats()['progressScore'],
            'custom_class' => 'bar-project',
        ];

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
