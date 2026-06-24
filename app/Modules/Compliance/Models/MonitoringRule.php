<?php

namespace App\Modules\Compliance\Models;

use App\Models\Control;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringRule extends Model
{
    use HasFactory;

    protected $table = 'comp_monitoring_rules';

    protected $fillable = [
        'control_id', 'name', 'description', 'rule_type',
        'check_expression', 'schedule_cron', 'threshold_value',
        'severity', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'threshold_value' => 'decimal:2',
    ];

    public function control()
    {
        return $this->belongsTo(Control::class, 'control_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function monitors()
    {
        return $this->hasMany(ControlMonitor::class, 'monitoring_rule_id');
    }
}
