<?php

namespace App\Modules\Governance\Database\Factories;

use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

class PolicyApprovalFactory extends Factory
{
    protected $model = PolicyApproval::class;

    public function definition(): array
    {
        return [
            'policy_id' => Policy::factory(),
            'policy_version_id' => null,
            'approver_user_id' => \App\Models\User::factory(),
            'approval_type' => 'initial',
            'status' => 'pending',
            'comments' => null,
            'rejection_reason' => null,
            'approved_at' => null,
            'rejected_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'approved',
            'approved_at' => now(),
            'comments' => fake()->sentence(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
