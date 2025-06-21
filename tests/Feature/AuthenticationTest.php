<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Authentication', function () {
    it('can access login page', function () {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    });

    it('can authenticate with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'active' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    });

    it('cannot authenticate with invalid credentials', function () {
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    });

    it('cannot authenticate inactive user', function () {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'active' => false,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
    });

    it('redirects to admin after login', function () {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'active' => true,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
    });

    it('can logout', function () {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post('/admin/logout');

        $this->assertGuest();
    });
});
