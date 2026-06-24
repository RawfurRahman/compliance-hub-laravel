<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RiskReview extends Model
{
    protected $table = 'risk_reviews';
    protected $fillable = [
        'risk_register_id', 'reviewed_by', 'review_date', 'outcome',
        'findings', 'recommendations', 'next_review_date',
    ];
    protected $casts = ['review_date' => 'date', 'next_review_date' => 'date'];

    public function risk() { return $this->belongsTo(RiskRegister::class, 'risk_register_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
