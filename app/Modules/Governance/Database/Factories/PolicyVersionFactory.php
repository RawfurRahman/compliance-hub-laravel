<?php

namespace App\Modules\Governance\Database\Factories;

use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class PolicyVersionFactory extends Factory
{
    protected $model = PolicyVersion::class;

    public function definition(): array
    {
        return [
            'policy_id' => Policy::factory(),
            'version_number' => 1,
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(5, true),
            'change_summary' => null,
            'status' => 'draft',
            'effective_date' => null,
            'expires_at' => null,
        ];
    }
}
