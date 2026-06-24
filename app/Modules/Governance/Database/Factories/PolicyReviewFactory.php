<?php

namespace App\Modules\Governance\Database\Factories;

use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyReview;
use Illuminate\Database\Eloquent\Factories\Factory;

class PolicyReviewFactory extends Factory
{
    protected $model = PolicyReview::class;

    public function definition(): array
    {
        return [
            'policy_id' => Policy::factory(),
            'policy_version_id' => null,
            'reviewer_user_id' => \App\Models\User::factory(),
            'review_type' => fake()->randomElement(['scheduled', 'ad_hoc', 'pre_approval']),
            'comments' => null,
            'status' => 'pending',
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'completed',
            'completed_at' => now(),
            'comments' => fake()->paragraph(),
        ]);
    }
}
