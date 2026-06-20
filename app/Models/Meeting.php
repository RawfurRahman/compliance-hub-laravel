<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'scheduled_at',
        'status',
        'meeting_link',
        'additional_emails',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'additional_emails' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees()
    {
        return $this->belongsToMany(User::class, 'meeting_user')->withTimestamps();
    }
}
