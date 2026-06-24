<?php

namespace App\Modules\Compliance\Models;

use App\Models\Control;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ControlEvidence extends Model
{
    use HasFactory;

    protected $table = 'comp_control_evidence';

    protected $fillable = [
        'control_id', 'title', 'description', 'file_path',
        'file_name', 'mime_type', 'evidence_date', 'collected_by', 'is_current',
    ];

    protected $casts = [
        'evidence_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function control()
    {
        return $this->belongsTo(Control::class, 'control_id');
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
