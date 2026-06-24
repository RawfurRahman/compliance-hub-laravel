<?php

namespace App\Modules\Governance\Database\Factories;

use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyWaiver;
use Illuminate\Database\Eloquent\Factories\Factory;

class PolicyWaiverFactory extends Factory
{
    protected $model = PolicyWaiver::class;

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
            'expires_at' => now()->addMonths(3)->toDateString(),
            'department' => fake()->randomElement(['IT', 'Security', 'Compliance']),
            'compensating_controls' => null,
            'rejection_reason' => null,
        ];
    }
}
