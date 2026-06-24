<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RiskNotification extends Model
{
    protected $table = 'risk_notifications';
    protected $fillable = [
        'risk_register_id', 'user_id', 'type', 'title', 'body', 'read_at', 'emailed',
    ];
    protected $casts = ['read_at' => 'datetime', 'emailed' => 'boolean'];

    public function risk() { return $this->belongsTo(RiskRegister::class, 'risk_register_id'); }
    public function user() { return $this->belongsTo(User::class); }

    public function scopeUnread($query) { return $query->whereNull('read_at'); }
}
