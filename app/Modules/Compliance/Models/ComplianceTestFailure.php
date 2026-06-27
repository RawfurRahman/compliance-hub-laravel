<?php

namespace App\Modules\Compliance\Models;

use App\Modules\Compliance\Models\ComplianceTest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceTestFailure extends Model
{
    use HasFactory;

    protected $table = 'comp_compliance_test_failures';

    protected $fillable = [
        'compliance_test_id',
        'failing_entity_description',
        'detected_at',
        'resolved_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function complianceTest()
    {
        return $this->belongsTo(ComplianceTest::class, 'compliance_test_id');
    }
}