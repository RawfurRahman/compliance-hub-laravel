<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function risks()
    {
        return $this->belongsToMany(RiskRegister::class, 'risk_register_tags', 'tag_id', 'risk_register_id');
    }
}
