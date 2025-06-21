<?php

use App\Models\Vmail;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh');
});

test('vmail model can be created with clearpw field', function () {
    $vmail = Vmail::factory()->create([
        'user' => 'test@example.com',
        'clearpw' => 'testpassword123',
        'active' => true,
    ]);

    expect($vmail->user)->toBe('test@example.com');
    expect($vmail->clearpw)->toBe('testpassword123');
    expect($vmail->active)->toBeTrue();
    expect($vmail->password)->not->toBe('testpassword123'); // Should be hashed
    expect($vmail->password)->not->toBeNull();
});

test('vmail password is automatically hashed when clearpw is set', function () {
    $vmail = new Vmail;
    $clearPassword = 'mypassword123';

    $vmail->clearpw = $clearPassword;

    expect($vmail->clearpw)->toBe($clearPassword);
    expect($vmail->password)->not->toBe($clearPassword);
    expect($vmail->password)->not->toBeNull();
});

test('vmail home directory is automatically generated', function () {
    $vmail = Vmail::factory()->create([
        'user' => 'testuser@example.com',
        'clearpw' => 'password',
    ]);

    expect($vmail->home)->toBe('/home/u/example.com/testuser');
});

test('vmail domain attribute is extracted correctly', function () {
    $vmail = new Vmail(['user' => 'user@testdomain.com']);

    expect($vmail->getDomainAttribute())->toBe('testdomain.com');
});

test('vmail scopes work correctly', function () {
    Vmail::factory()->create(['user' => 'active@test.com', 'clearpw' => 'pass', 'active' => true]);
    Vmail::factory()->create(['user' => 'inactive@test.com', 'clearpw' => 'pass', 'active' => false]);
    Vmail::factory()->create(['user' => 'user@domain.com', 'clearpw' => 'pass', 'active' => true]);

    $activeEmails = Vmail::active()->get();
    $testDomainEmails = Vmail::forDomain('test.com')->get();

    expect($activeEmails)->toHaveCount(2);
    expect($testDomainEmails)->toHaveCount(2);
});
