<?php

namespace Database\Factories;

use App\Models\Valias;
use Illuminate\Database\Eloquent\Factories\Factory;

class ValiasFactory extends Factory
{
    protected $model = Valias::class;

    public function definition(): array
    {
        $domain = $this->faker->domainName();
        $sourceUsername = $this->faker->userName();
        $targetUsername = $this->faker->userName();

        return [
            'source' => $sourceUsername.'@'.$domain,
            'target' => $targetUsername.'@'.$domain,
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    public function catchall(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => '@'.$this->faker->domainName(),
        ]);
    }

    public function forDomain(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => $this->faker->userName().'@'.$domain,
        ]);
    }

    public function multipleTargets(int $count = 3): static
    {
        $targets = [];
        for ($i = 0; $i < $count; $i++) {
            $targets[] = $this->faker->userName().'@'.$this->faker->domainName();
        }

        return $this->state(fn (array $attributes) => [
            'target' => implode(',', $targets),
        ]);
    }

    public function withTargets(array $targets): static
    {
        return $this->state(fn (array $attributes) => [
            'target' => implode(',', $targets),
        ]);
    }
}
