<?php

namespace Database\Factories;

use App\Models\Vhost;
use Illuminate\Database\Eloquent\Factories\Factory;

class VhostFactory extends Factory
{
    protected $model = Vhost::class;

    public function definition(): array
    {
        return [
            'domain' => $this->faker->unique()->domainName(),
            'status' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => true,
        ]);
    }
}
