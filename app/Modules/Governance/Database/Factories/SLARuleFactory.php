<?php

namespace App\Modules\Governance\Database\Factories;

use App\Modules\Governance\Models\SLARule;
use Illuminate\Database\Eloquent\Factories\Factory;

class SLARuleFactory extends Factory
{
    protected $model = SLARule::class;

    public function definition(): array
    {
        return [
            'policy_id' => null,
            'name' => fake()->unique()->randomElement([
                'Review Completion SLA',
                'Approval Completion SLA',
                'Publication SLA',
            ]),
            'description' => fake()->sentence(),
            'trigger_event' => fake()->randomElement(['policy_submitted', 'review_requested', 'approval_requested']),
            'action_type' => fake()->randomElement(['review_completion', 'approval_completion', 'publication']),
            'sla_hours' => fake()->randomElement([24, 48, 72]),
            'escalation_interval_hours' => fake()->randomElement([4, 8, 24]),
            'escalation_user_id' => null,
            'is_active' => true,
        ];
    }
}
