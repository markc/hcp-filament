<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('User Model', function () {
    it('can create a user with valid data', function () {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => User::ROLE_CUSTOMER,
        ]);

        expect($user->name)->toBe('Test User');
        expect($user->email)->toBe('test@example.com');
        expect($user->role)->toBe(User::ROLE_CUSTOMER);
        expect($user->active)->toBeTrue();
    });

    it('has correct role constants', function () {
        expect(User::ROLE_ADMIN)->toBe('admin');
        expect(User::ROLE_AGENT)->toBe('agent');
        expect(User::ROLE_CUSTOMER)->toBe('customer');
    });

    it('has correct customer type constants', function () {
        expect(User::CUSTOMER_TYPE_INDIVIDUAL)->toBe('individual');
        expect(User::CUSTOMER_TYPE_BUSINESS)->toBe('business');
    });

    it('can check if user is admin', function () {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        expect($admin->isAdmin())->toBeTrue();
        expect($customer->isAdmin())->toBeFalse();
    });

    it('can check if user is agent', function () {
        $agent = User::factory()->create(['role' => User::ROLE_AGENT]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        expect($agent->isAgent())->toBeTrue();
        expect($customer->isAgent())->toBeFalse();
    });

    it('can check if user is customer', function () {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        expect($customer->isCustomer())->toBeTrue();
        expect($admin->isCustomer())->toBeFalse();
    });

    it('can scope users by role', function () {
        User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['role' => User::ROLE_AGENT]);
        User::factory()->count(3)->create(['role' => User::ROLE_CUSTOMER]);

        expect(User::admins()->count())->toBe(1);
        expect(User::agents()->count())->toBe(1);
        expect(User::customers()->count())->toBe(3);
    });

    it('can scope active users', function () {
        User::factory()->create(['active' => true]);
        User::factory()->create(['active' => true]);
        User::factory()->create(['active' => false]);

        expect(User::active()->count())->toBe(2);
    });

    it('can check if user can access panel', function () {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'active' => true]);
        $agent = User::factory()->create(['role' => User::ROLE_AGENT, 'active' => true]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER, 'active' => true]);
        $inactiveAdmin = User::factory()->create(['role' => User::ROLE_ADMIN, 'active' => false]);

        expect($admin->canAccessPanel(null))->toBeTrue();
        expect($agent->canAccessPanel(null))->toBeTrue();
        expect($customer->canAccessPanel(null))->toBeFalse();
        expect($inactiveAdmin->canAccessPanel(null))->toBeFalse();
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'name', 'first_name', 'last_name', 'email', 'email_verified_at', 'password',
            'role', 'active', 'phone', 'company', 'department', 'customer_type',
            'address', 'city', 'state', 'postal_code', 'country',
            'account_balance', 'subscription_expires', 'notes',
        ];

        $user = new User;
        expect($user->getFillable())->toBe($fillable);
    });

    it('hides sensitive attributes', function () {
        $hidden = ['password', 'remember_token'];

        $user = new User;
        expect($user->getHidden())->toBe($hidden);
    });

    it('casts attributes correctly', function () {
        $user = new User;
        $casts = $user->getCasts();

        expect($casts['email_verified_at'])->toBe('datetime');
        expect($casts['password'])->toBe('hashed');
        expect($casts['active'])->toBe('boolean');
        expect($casts['account_balance'])->toBe('decimal:2');
        expect($casts['subscription_expires'])->toBe('datetime');
    });

    it('validates email uniqueness', function () {
        User::factory()->create(['email' => 'test@example.com']);

        expect(function () {
            User::factory()->create(['email' => 'test@example.com']);
        })->toThrow();
    });

    it('defaults to customer role', function () {
        $user = User::factory()->make();
        expect($user->role)->toBe(User::ROLE_CUSTOMER);
    });

    it('defaults to active status', function () {
        $user = User::factory()->make();
        expect($user->active)->toBeTrue();
    });

    it('can get full name', function () {
        $user = User::factory()->make([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        expect($user->getFullNameAttribute())->toBe('John Doe');
    });

    it('handles null first or last name in full name', function () {
        $user = User::factory()->make([
            'first_name' => 'John',
            'last_name' => null,
        ]);

        expect($user->getFullNameAttribute())->toBe('John');

        $user = User::factory()->make([
            'first_name' => null,
            'last_name' => 'Doe',
        ]);

        expect($user->getFullNameAttribute())->toBe('Doe');
    });
});
