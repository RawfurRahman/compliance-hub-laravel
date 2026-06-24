<?php

namespace App\Modules\Compliance\Models;

use App\Models\AssessmentFinding;
use App\Models\Control;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ControlTest extends Model
{
    use HasFactory;

    protected $table = 'comp_control_tests';

    protected $fillable = [
        'control_id', 'assessment_finding_id', 'tested_by',
        'test_type', 'test_date', 'result', 'score',
        'notes', 'evidence_summary', 'framework_version_id',
    ];

    protected $casts = [
        'test_date' => 'datetime',
        'score' => 'decimal:2',
    ];

    public function control()
    {
        return $this->belongsTo(Control::class, 'control_id');
    }

    public function assessmentFinding()
    {
        return $this->belongsTo(AssessmentFinding::class, 'assessment_finding_id');
    }

    public function testedBy()
    {
        return $this->belongsTo(User::class, 'tested_by');
    }

    public function frameworkVersion()
    {
        return $this->belongsTo(FrameworkVersion::class, 'framework_version_id');
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('result', ['fail', 'error']);
    }

    public function scopePassed($query)
    {
        return $query->where('result', 'pass');
    }
}
