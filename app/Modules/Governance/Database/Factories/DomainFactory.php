<?php

namespace App\Modules\Governance\Database\Factories;

use App\Modules\Governance\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DomainFactory extends Factory
{
    protected $model = Domain::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Information Security',
            'Data Privacy',
            'Operational Resilience',
            'Business Continuity',
            'IT Governance',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
