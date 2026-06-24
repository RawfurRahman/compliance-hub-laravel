<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskComment extends Model
{
    use SoftDeletes;

    protected $table = 'risk_comments';

    protected $fillable = [
        'risk_register_id',
        'user_id',
        'body',
    ];

    public function risk()
    {
        return $this->belongsTo(RiskRegister::class, 'risk_register_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
