<?php

namespace Database\Factories;

use App\Models\Vmail;
use Illuminate\Database\Eloquent\Factories\Factory;

class VmailFactory extends Factory
{
    protected $model = Vmail::class;

    public function definition(): array
    {
        $domain = $this->faker->domainName();
        $username = $this->faker->userName();

        return [
            'user' => $username.'@'.$domain,
            'password' => 'password123',
            'uid' => 1000,
            'gid' => 1000,
            'home' => '/home/u/'.$domain.'/'.$username,
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

    public function forDomain(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'user' => $this->faker->userName().'@'.$domain,
            'home' => '/home/u/'.$domain.'/'.explode('@', $attributes['user'])[0],
        ]);
    }

    public function withCustomHome(string $home): static
    {
        return $this->state(fn (array $attributes) => [
            'home' => $home,
        ]);
    }
}
