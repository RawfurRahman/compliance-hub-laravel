<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'module_type',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pciDssDetails()
    {
        return $this->hasOne(ProjectPciDssDetail::class);
    }

    // ** NEW RELATIONSHIPS **
    public function evidence()
    {
        return $this->hasMany(Evidence::class);
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}
