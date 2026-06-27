<?php

namespace App\Modules\Compliance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceTestTemplate extends Model
{
    use HasFactory;

    protected $table = 'comp_compliance_test_templates';

    protected $fillable = [
        'name',
        'description',
        'integration_type',
        'test_type',
        'sla_days',
        'check_expression',
        'config',
    ];

    protected $casts = [
        'config' => 'json',
        'sla_days' => 'integer',
    ];
}
