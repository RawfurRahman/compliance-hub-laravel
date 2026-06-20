<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidence extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'requirement_id',
        'name', // Original file name
        'path', // Stored file path
        'url',  // Publicly accessible URL (if applicable)
        'description',
    ];

    /**
     * Get the requirement that owns the evidence.
     */
    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }
}
