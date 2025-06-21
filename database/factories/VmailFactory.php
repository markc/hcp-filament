<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vmail>
 */
class VmailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $password = fake()->password(8, 16);
        $domain = fake()->domainName();
        $username = fake()->userName();

        return [
            'user' => $username.'@'.$domain,
            'clearpw' => $password,
            'uid' => fake()->numberBetween(1000, 9999),
            'gid' => fake()->numberBetween(1000, 9999),
            'active' => fake()->boolean(80),
        ];
    }
}
