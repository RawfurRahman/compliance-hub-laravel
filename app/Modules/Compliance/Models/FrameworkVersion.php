<?php

namespace App\Modules\Compliance\Models;

use App\Models\Framework;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrameworkVersion extends Model
{
    use HasFactory;

    protected $table = 'comp_framework_versions';

    protected $fillable = [
        'framework_id', 'version', 'release_date', 'description',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'release_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function framework()
    {
        return $this->belongsTo(Framework::class, 'framework_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function controlMaps()
    {
        return $this->hasMany(FrameworkControlMap::class, 'framework_version_id');
    }
}
