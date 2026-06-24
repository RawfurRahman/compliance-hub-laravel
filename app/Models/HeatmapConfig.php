<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeatmapConfig extends Model
{
    use HasFactory;

    protected $table = 'heatmap_config';

    protected $fillable = [
        'critical_threshold',
        'high_threshold',
        'medium_threshold',
        'low_threshold',
    ];
}
