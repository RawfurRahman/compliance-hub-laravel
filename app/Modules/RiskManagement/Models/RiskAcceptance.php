<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskAcceptance extends Model
{
    use HasFactory;

    protected $table = 'risk_acceptances';

    protected $fillable = [
        'risk_register_id',
        'requested_by',
        'approved_by',
        'justification',
        'expiry_date',
        'status',
        'residual_risk_score',
        'acceptance_criteria',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'expiry_date'  => 'date',
        'reviewed_at'  => 'datetime',
        'residual_risk_score' => 'integer',
    ];

    public function risk()
    {
        return $this->belongsTo(RiskRegister::class, 'risk_register_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
