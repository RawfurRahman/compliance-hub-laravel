<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'report_type',
        'recipient_email',
        'frequency',
        'format',
        'last_sent_at',
        'next_run_at',
    ];

    protected $casts = [
        'last_sent_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * Get the project that owns the schedule.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Calculate and update next run time based on frequency.
     */
    public function calculateNextRun(): void
    {
        $now = now();
        
        switch ($this->frequency) {
            case 'daily':
                $this->next_run_at = $now->addDay()->startOfDay();
                break;
            case 'weekly':
                $this->next_run_at = $now->addWeek()->startOfDay();
                break;
            case 'monthly':
                $this->next_run_at = $now->addMonth()->startOfDay();
                break;
            default:
                $this->next_run_at = $now->addDay()->startOfDay();
                break;
        }
    }
}
