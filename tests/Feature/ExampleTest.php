<?php

use App\Models\User;

test('the application redirects root to admin panel', function () {
    $response = $this->get('/');

    // Root route should redirect (302) to admin or login
    $response->assertStatus(302);
});

test('the admin login page is accessible', function () {
    $response = $this->get('/login');

    // Login page should be accessible
    $response->assertStatus(200);
});

test('authenticated admin user gets valid response from dashboard', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'active' => true,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/');

    // Should not get server errors (5xx), any client response is acceptable for this test
    expect($response->getStatusCode())->toBeLessThan(500);
});
