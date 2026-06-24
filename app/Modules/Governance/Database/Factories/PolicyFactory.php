<?php

namespace App\Modules\Governance\Database\Factories;

use App\Modules\Governance\Models\Domain;
use App\Modules\Governance\Models\Policy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PolicyFactory extends Factory
{
    protected $model = Policy::class;

    public function definition(): array
    {
        $title = fake()->unique()->randomElement([
            'Information Security Policy',
            'Data Classification Policy',
            'Business Continuity Plan Policy',
            'Acceptable Use Policy',
            'Access Control Policy',
            'Incident Response Policy',
            'Password Policy',
            'Remote Access Policy',
        ]);

        return [
            'domain_id' => Domain::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(6),
            'policy_number' => 'GOV-POL-' . str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'description' => fake()->paragraph(),
            'status' => 'draft',
            'effective_date' => null,
            'expires_at' => null,
            'owner_user_id' => null,
            'department' => fake()->randomElement(['IT', 'Security', 'Compliance', 'Operations']),
            'business_unit' => fake()->randomElement(['Corporate', 'Division A', 'Division B']),
            'current_version' => 0,
            'is_active' => true,
            'published_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'draft']);
    }

    public function underReview(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'under_review']);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'approved']);
    }

    public function published(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'published',
            'effective_date' => now()->subDay()->toDateString(),
            'published_at' => now()->subDay(),
        ]);
    }

    public function deprecated(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'deprecated']);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'archived']);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'expired',
            'expires_at' => now()->subDay()->toDateString(),
        ]);
    }
}
