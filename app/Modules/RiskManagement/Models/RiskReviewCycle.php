<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RiskReviewCycle extends Model
{
    protected $table = 'risk_review_cycles';
    protected $fillable = [
        'project_id', 'name', 'frequency', 'next_review_date', 'last_review_date', 'reviewer_id', 'is_active',
    ];
    protected $casts = [
        'next_review_date' => 'date',
        'last_review_date' => 'date',
        'is_active'        => 'boolean',
    ];

    public function project() { return $this->belongsTo(Project::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewer_id'); }
}
