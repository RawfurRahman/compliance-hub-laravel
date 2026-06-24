<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'risk_assets';
    protected $fillable = [
        'project_id', 'asset_id', 'name', 'asset_type',
        'owner', 'custodian', 'asset_value', 'description', 'location',
    ];
    protected $casts = ['asset_value' => 'integer'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function risks()
    {
        return $this->hasMany(RiskRegister::class, 'risk_asset_id');
    }
}
