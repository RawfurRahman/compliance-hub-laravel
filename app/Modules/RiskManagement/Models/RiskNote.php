<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskNote extends Model
{
    use SoftDeletes;

    protected $table = 'risk_notes';

    protected $fillable = [
        'notable_id', 'notable_type', 'user_id', 'type',
        'content', 'file_path', 'file_name', 'mime_type',
    ];

    public function notable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
