<?php

namespace App\Models;

use App\Modules\Compliance\Models\ComplianceTest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use HasFactory;

    protected $table = 'integrations';

    protected $fillable = [
        'project_id',
        'name',
        'type',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => 'json',
        'is_active' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function complianceTests()
    {
        return $this->hasMany(ComplianceTest::class, 'integration_id');
    }
}
