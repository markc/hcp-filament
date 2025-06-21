<?php

use App\Models\User;

test('the application redirects root to admin panel', function () {
    $response = $this->get('/');

    // Root route should redirect (302) to admin or login
    $response->assertStatus(302);
});

test('the admin login page is accessible', function () {
    $response = $this->get('/admin/login');

    // Login page should be accessible
    $response->assertStatus(200);
});

test('authenticated user can access admin panel', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'active' => true,
    ]);

    $response = $this->actingAs($user)->get('/admin');

    // Should either show dashboard or redirect to dashboard
    $response->assertStatus([200, 302]);
});
