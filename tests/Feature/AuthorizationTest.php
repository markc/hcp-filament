<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Authorization', function () {
    it('admin can access admin panel', function () {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    });

    it('agent can access admin panel', function () {
        $agent = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'active' => true,
        ]);

        $response = $this->actingAs($agent)->get('/admin');

        $response->assertStatus(200);
    });

    it('customer cannot access admin panel', function () {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'active' => true,
        ]);

        $response = $this->actingAs($customer)->get('/admin');

        $response->assertRedirect('/admin/login');
    });

    it('inactive user cannot access admin panel', function () {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'active' => false,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertRedirect('/admin/login');
    });

    it('guest cannot access admin panel', function () {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    });

    it('admin can access user management', function () {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200);
    });

    it('agent can access user management', function () {
        $agent = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'active' => true,
        ]);

        $response = $this->actingAs($agent)->get('/admin/users');

        $response->assertStatus(200);
    });

    it('admin can access mail management', function () {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/vhosts');

        $response->assertStatus(200);
    });

    it('agent can access mail management', function () {
        $agent = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'active' => true,
        ]);

        $response = $this->actingAs($agent)->get('/admin/vmails');

        $response->assertStatus(200);
    });

    it('admin can access system information', function () {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/info-sys');

        $response->assertStatus(200);
    });

    it('agent can access system information', function () {
        $agent = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'active' => true,
        ]);

        $response = $this->actingAs($agent)->get('/admin/info-mail');

        $response->assertStatus(200);
    });
});
