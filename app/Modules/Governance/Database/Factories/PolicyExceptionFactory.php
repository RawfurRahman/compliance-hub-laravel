<?php

namespace App\Modules\Governance\Database\Factories;

use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyException;
use Illuminate\Database\Eloquent\Factories\Factory;

class PolicyExceptionFactory extends Factory
{
    protected $model = PolicyException::class;

    public function definition(): array
    {
        return [
            'policy_id' => Policy::factory(),
            'policy_version_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'justification' => fake()->paragraph(),
            'requested_by' => \App\Models\User::factory(),
            'approved_by' => null,
            'status' => 'pending',
            'effective_date' => now()->toDateString(),
            'expires_at' => now()->addMonths(6)->toDateString(),
            'department' => fake()->randomElement(['IT', 'Security', 'Compliance']),
            'risk_acceptance' => null,
            'rejection_reason' => null,
        ];
    }
}
