<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Requirement extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'req_num',
        'req_description',
        'long_description', // Assuming you have this column for detailed descriptions
    ];

    /**
     * Get the project that owns the requirement.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the evidence associated with the requirement.
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }
}
