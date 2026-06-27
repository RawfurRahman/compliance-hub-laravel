<?php

namespace App\Modules\Compliance\Models;

use App\Models\User;
use App\Models\Framework;
use App\Models\Control;
use App\Models\Integration;
use App\Modules\Compliance\Models\ControlMonitor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceTest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'comp_compliance_tests';

    protected $fillable = [
        'name',
        'description',
        'owner_user_id',
        'team',
        'test_type',
        'sla_days',
        'status',
        'last_run_at',
        'next_due_at',
        'control_monitor_id',
        'integration_id',
        'control_id',
    ];

    protected $casts = [
        'last_run_at' => 'datetime',
        'next_due_at' => 'datetime',
        'sla_days' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function ownerUser()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function frameworkLinks()
    {
        return $this->hasMany(ComplianceTestFrameworkLink::class, 'compliance_test_id');
    }

    public function failures()
    {
        return $this->hasMany(ComplianceTestFailure::class, 'compliance_test_id');
    }

    public function activeFailures()
    {
        return $this->hasMany(ComplianceTestFailure::class, 'compliance_test_id')
            ->whereNull('resolved_at');
    }

    public function controlMonitor()
    {
        return $this->belongsTo(ControlMonitor::class, 'control_monitor_id');
    }

    public function integration()
    {
        return $this->belongsTo(Integration::class, 'integration_id');
    }

    public function control()
    {
        return $this->belongsTo(Control::class, 'control_id');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'Passing')
            ->whereNotNull('next_due_at')
            ->where('next_due_at', '<', now());
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'Passing'
            && $this->next_due_at !== null
            && $this->next_due_at->isPast();
    }

    public function getPassRateAttribute(): float
    {
        $totalTests = $this->failures()->count() + 1;

        if ($totalTests === 0) {
            return 0.0;
        }

        $passedTests = $this->failures()->whereNotNull('resolved_at')->count() + 1;

        return round(($passedTests / $totalTests) * 100, 2);
    }
}