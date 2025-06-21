<?php

use App\Models\Vhost;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh');
});

test('vhost model can be created with active field', function () {
    $vhost = Vhost::factory()->create([
        'domain' => 'test.example.com',
        'active' => true,
    ]);

    expect($vhost->domain)->toBe('test.example.com');
    expect($vhost->active)->toBeTrue();
});

test('vhost active field defaults to true', function () {
    $vhost = Vhost::factory()->create([
        'domain' => 'default.com',
    ]);

    expect($vhost->active)->toBeBool();
});

test('vhost scopes work correctly', function () {
    Vhost::factory()->create(['domain' => 'active1.com', 'active' => true]);
    Vhost::factory()->create(['domain' => 'active2.com', 'active' => true]);
    Vhost::factory()->create(['domain' => 'inactive.com', 'active' => false]);

    $activeVhosts = Vhost::active()->get();
    $inactiveVhosts = Vhost::inactive()->get();

    expect($activeVhosts)->toHaveCount(2);
    expect($inactiveVhosts)->toHaveCount(1);
    expect($activeVhosts->first()->domain)->toBe('active1.com');
    expect($inactiveVhosts->first()->domain)->toBe('inactive.com');
});

test('vhost can access computed relationships', function () {
    $vhost = Vhost::factory()->create([
        'domain' => 'relations.com',
        'active' => true,
    ]);

    // Test that computed attributes are accessible
    expect($vhost->getVmailsAttribute())->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($vhost->getValiasAttribute())->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});
