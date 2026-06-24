<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RiskAcceptanceRequest extends Model
{
    protected $table = 'risk_acceptance_requests';
    protected $fillable = [
        'risk_register_id', 'requested_by', 'approved_by',
        'justification', 'conditions', 'status', 'expiry_date', 'approver_notes', 'decided_at',
    ];
    protected $casts = ['expiry_date' => 'date', 'decided_at' => 'datetime'];

    public function risk() { return $this->belongsTo(RiskRegister::class, 'risk_register_id'); }
    public function requestedBy() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
}
