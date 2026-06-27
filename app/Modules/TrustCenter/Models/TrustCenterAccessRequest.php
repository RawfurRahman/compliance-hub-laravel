<?php

namespace App\Modules\TrustCenter\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustCenterAccessRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'trust_center_id',
        'requester_name',
        'requester_email',
        'requester_company',
        'note',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function trustCenter(): BelongsTo
    {
        return $this->belongsTo(TrustCenter::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', 'Pending');
    }
}
