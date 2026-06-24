<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RiskStatusHistory extends Model
{
    public $timestamps = false;
    protected $table = 'risk_status_history';
    protected $fillable = ['risk_register_id', 'from_status', 'to_status', 'reason', 'changed_by', 'changed_at'];
    protected $casts = ['changed_at' => 'datetime'];

    public function risk() { return $this->belongsTo(RiskRegister::class, 'risk_register_id'); }
    public function changedBy() { return $this->belongsTo(User::class, 'changed_by'); }
}
