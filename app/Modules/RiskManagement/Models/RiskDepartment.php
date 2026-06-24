<?php

namespace App\Modules\RiskManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskDepartment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'risk_departments';
    protected $fillable = ['name', 'code', 'head_name', 'head_email', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function risks()
    {
        return $this->hasMany(RiskRegister::class, 'risk_department_id');
    }
}
