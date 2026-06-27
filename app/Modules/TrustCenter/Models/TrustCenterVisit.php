<?php

namespace App\Modules\TrustCenter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustCenterVisit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'trust_center_id',
        'visited_at',
        'ip_hash',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function trustCenter(): BelongsTo
    {
        return $this->belongsTo(TrustCenter::class);
    }
}
