<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make('password'),
                'active' => true,
                'department' => 'IT',
            ]
        );

        // Create agent user
        User::firstOrCreate(
            ['email' => 'agent@example.com'],
            [
                'name' => 'Agent User',
                'first_name' => 'John',
                'last_name' => 'Agent',
                'role' => User::ROLE_AGENT,
                'password' => Hash::make('password'),
                'active' => true,
                'department' => 'Support',
                'phone' => '+1-555-0123',
            ]
        );

        // Create customer users
        User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Individual Customer',
                'first_name' => 'Jane',
                'last_name' => 'Customer',
                'role' => User::ROLE_CUSTOMER,
                'password' => Hash::make('password'),
                'active' => true,
                'customer_type' => User::CUSTOMER_TYPE_INDIVIDUAL,
                'phone' => '+1-555-0456',
                'account_balance' => 100.00,
                'subscription_expires' => now()->addDays(30),
            ]
        );

        User::firstOrCreate(
            ['email' => 'business@example.com'],
            [
                'name' => 'Business Customer',
                'first_name' => 'Bob',
                'last_name' => 'Business',
                'company' => 'Example Corp',
                'role' => User::ROLE_CUSTOMER,
                'password' => Hash::make('password'),
                'active' => true,
                'customer_type' => User::CUSTOMER_TYPE_BUSINESS,
                'phone' => '+1-555-0789',
                'address' => '123 Business Street',
                'city' => 'Business City',
                'state' => 'BC',
                'postal_code' => '12345',
                'country' => 'USA',
                'account_balance' => 500.00,
                'subscription_expires' => now()->addDays(365),
            ]
        );
    }
}
