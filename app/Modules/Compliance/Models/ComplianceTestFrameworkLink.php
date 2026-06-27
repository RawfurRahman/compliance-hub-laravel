<?php

namespace App\Modules\Compliance\Models;

use App\Modules\Compliance\Models\ComplianceTest;
use App\Models\Framework;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceTestFrameworkLink extends Model
{
    use HasFactory;

    protected $table = 'comp_compliance_test_framework_links';

    protected $fillable = [
        'compliance_test_id',
        'framework_id',
        'resources_in_scope_count',
    ];

    public function complianceTest()
    {
        return $this->belongsTo(ComplianceTest::class, 'compliance_test_id');
    }

    public function framework()
    {
        return $this->belongsTo(Framework::class, 'framework_id');
    }
}