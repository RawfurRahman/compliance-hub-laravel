<?php

namespace App\Modules\Compliance\Models;

use App\Models\Control;
use App\Models\FrameworkControl;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrameworkControlMap extends Model
{
    use HasFactory;

    protected $table = 'comp_framework_control_map';

    protected $fillable = [
        'control_id', 'framework_control_id', 'framework_version_id',
        'mapping_type', 'mapping_notes', 'effectiveness_weight', 'created_by',
    ];

    protected $casts = [
        'effectiveness_weight' => 'decimal:2',
    ];

    public function control()
    {
        return $this->belongsTo(Control::class, 'control_id');
    }

    public function frameworkControl()
    {
        return $this->belongsTo(FrameworkControl::class, 'framework_control_id');
    }

    public function frameworkVersion()
    {
        return $this->belongsTo(FrameworkVersion::class, 'framework_version_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
