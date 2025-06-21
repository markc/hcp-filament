<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'active' => true,
        ]);

        // Create some sample virtual hosts
        \App\Models\Vhost::factory()->create([
            'domain' => 'example.com',
            'active' => true,
        ]);

        \App\Models\Vhost::factory()->create([
            'domain' => 'test.org',
            'active' => true,
        ]);

        \App\Models\Vhost::factory()->create([
            'domain' => 'inactive.com',
            'active' => false,
        ]);

        // Create some sample mailboxes
        \App\Models\Vmail::factory()->create([
            'user' => 'admin@example.com',
            'clearpw' => 'password123',
            'uid' => 1001,
            'gid' => 1001,
            'active' => true,
        ]);

        \App\Models\Vmail::factory()->create([
            'user' => 'user@example.com',
            'clearpw' => 'userpass',
            'uid' => 1002,
            'gid' => 1002,
            'active' => true,
        ]);

        \App\Models\Vmail::factory()->create([
            'user' => 'test@test.org',
            'clearpw' => 'testpass',
            'uid' => 1003,
            'gid' => 1003,
            'active' => true,
        ]);

        \App\Models\Vmail::factory()->create([
            'user' => 'disabled@example.com',
            'clearpw' => 'disabled123',
            'uid' => 1004,
            'gid' => 1004,
            'active' => false,
        ]);

        // Create some aliases
        \App\Models\Valias::factory()->create([
            'source' => 'contact@example.com',
            'target' => 'admin@example.com',
            'active' => true,
        ]);

        \App\Models\Valias::factory()->create([
            'source' => '@example.com',
            'target' => 'admin@example.com',
            'active' => true,
        ]);

        \App\Models\Valias::factory()->create([
            'source' => 'old@example.com',
            'target' => 'admin@example.com',
            'active' => false,
        ]);
    }
}
