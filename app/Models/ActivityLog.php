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
        'role',
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

        // Automatically capture role when creating a log entry
        static::creating(function ($log) {
            if (auth()->check() && auth()->user()) {
                $log->role = auth()->user()->hasRole('Super Admin') 
                    ? 'Super Admin' 
                    : auth()->user()->roles()->pluck('name')->first() 
                    ?? 'Guest';
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
