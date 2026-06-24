<?php

namespace App\Modules\RiskManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'risk_categories';
    protected $fillable = ['name', 'slug', 'color', 'description', 'is_active', 'sort_order'];
    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function risks()
    {
        return $this->hasMany(RiskRegister::class, 'risk_category_id');
    }
}
