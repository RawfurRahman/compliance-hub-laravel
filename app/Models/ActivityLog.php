<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'details',
        'ip_address',
    ];

    protected $casts = [
        'details' => 'json',
        'created_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        // Enforce immutability
        static::updating(function ($model) {
            return false;
        });

        static::deleting(function ($model) {
            return false;
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
