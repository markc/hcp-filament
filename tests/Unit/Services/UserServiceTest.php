<?php

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('UserService', function () {
    beforeEach(function () {
        $this->userService = new UserService;
    });

    it('can create a user', function () {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => User::ROLE_CUSTOMER,
            'first_name' => 'Test',
            'last_name' => 'User',
        ];

        $result = $this->userService->createUser($userData);

        expect($result['success'])->toBeTrue();
        expect($result['user'])->toBeInstanceOf(User::class);
        expect($result['user']->email)->toBe('test@example.com');
        expect($result['user']->role)->toBe(User::ROLE_CUSTOMER);
    });

    it('validates required fields when creating user', function () {
        $result = $this->userService->createUser([]);

        expect($result['success'])->toBeFalse();
        expect($result['errors'])->toBeArray();
        expect($result['errors'])->toHaveKey('name');
        expect($result['errors'])->toHaveKey('email');
        expect($result['errors'])->toHaveKey('password');
    });

    it('validates email uniqueness when creating user', function () {
        User::factory()->create(['email' => 'test@example.com']);

        $result = $this->userService->createUser([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => User::ROLE_CUSTOMER,
        ]);

        expect($result['success'])->toBeFalse();
        expect($result['errors'])->toHaveKey('email');
    });

    it('can update a user', function () {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ];

        $result = $this->userService->updateUser($user->id, $updateData);

        expect($result['success'])->toBeTrue();
        expect($result['user']->name)->toBe('Updated Name');
        expect($result['user']->first_name)->toBe('Updated');
        expect($result['user']->last_name)->toBe('Name');
    });

    it('can update user password', function () {
        $user = User::factory()->create();
        $newPassword = 'newpassword123';

        $result = $this->userService->updateUser($user->id, ['password' => $newPassword]);

        expect($result['success'])->toBeTrue();

        $user->refresh();
        expect(Hash::check($newPassword, $user->password))->toBeTrue();
    });

    it('can delete a user', function () {
        $user = User::factory()->create();

        $result = $this->userService->deleteUser($user->id);

        expect($result['success'])->toBeTrue();
        expect(User::find($user->id))->toBeNull();
    });

    it('returns error when deleting non-existent user', function () {
        $result = $this->userService->deleteUser(99999);

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('not found');
    });

    it('can get users by role', function () {
        User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->count(2)->create(['role' => User::ROLE_AGENT]);
        User::factory()->count(3)->create(['role' => User::ROLE_CUSTOMER]);

        $admins = $this->userService->getUsersByRole(User::ROLE_ADMIN);
        $agents = $this->userService->getUsersByRole(User::ROLE_AGENT);
        $customers = $this->userService->getUsersByRole(User::ROLE_CUSTOMER);

        expect($admins->count())->toBe(1);
        expect($agents->count())->toBe(2);
        expect($customers->count())->toBe(3);
    });

    it('can get active users only', function () {
        User::factory()->count(3)->create(['active' => true]);
        User::factory()->count(2)->create(['active' => false]);

        $activeUsers = $this->userService->getActiveUsers();

        expect($activeUsers->count())->toBe(3);
        expect($activeUsers->every(fn ($user) => $user->active))->toBeTrue();
    });

    it('can activate a user', function () {
        $user = User::factory()->create(['active' => false]);

        $result = $this->userService->activateUser($user->id);

        expect($result['success'])->toBeTrue();

        $user->refresh();
        expect($user->active)->toBeTrue();
    });

    it('can deactivate a user', function () {
        $user = User::factory()->create(['active' => true]);

        $result = $this->userService->deactivateUser($user->id);

        expect($result['success'])->toBeTrue();

        $user->refresh();
        expect($user->active)->toBeFalse();
    });

    it('can change user role', function () {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $result = $this->userService->changeUserRole($user->id, User::ROLE_AGENT);

        expect($result['success'])->toBeTrue();

        $user->refresh();
        expect($user->role)->toBe(User::ROLE_AGENT);
    });

    it('validates role when changing user role', function () {
        $user = User::factory()->create();

        $result = $this->userService->changeUserRole($user->id, 'invalid_role');

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('Invalid role');
    });

    it('can get user statistics', function () {
        User::factory()->create(['role' => User::ROLE_ADMIN, 'active' => true]);
        User::factory()->count(2)->create(['role' => User::ROLE_AGENT, 'active' => true]);
        User::factory()->count(3)->create(['role' => User::ROLE_CUSTOMER, 'active' => true]);
        User::factory()->create(['role' => User::ROLE_CUSTOMER, 'active' => false]);

        $stats = $this->userService->getUserStatistics();

        expect($stats['total'])->toBe(7);
        expect($stats['active'])->toBe(6);
        expect($stats['inactive'])->toBe(1);
        expect($stats['by_role'][User::ROLE_ADMIN])->toBe(1);
        expect($stats['by_role'][User::ROLE_AGENT])->toBe(2);
        expect($stats['by_role'][User::ROLE_CUSTOMER])->toBe(4);
    });

    it('can bulk activate users', function () {
        $users = User::factory()->count(3)->create(['active' => false]);
        $userIds = $users->pluck('id')->toArray();

        $result = $this->userService->bulkActivateUsers($userIds);

        expect($result['success'])->toBeTrue();
        expect($result['count'])->toBe(3);

        foreach ($users as $user) {
            $user->refresh();
            expect($user->active)->toBeTrue();
        }
    });

    it('can bulk deactivate users', function () {
        $users = User::factory()->count(3)->create(['active' => true]);
        $userIds = $users->pluck('id')->toArray();

        $result = $this->userService->bulkDeactivateUsers($userIds);

        expect($result['success'])->toBeTrue();
        expect($result['count'])->toBe(3);

        foreach ($users as $user) {
            $user->refresh();
            expect($user->active)->toBeFalse();
        }
    });

    it('can search users by name or email', function () {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@test.com']);
        User::factory()->create(['name' => 'Bob Wilson', 'email' => 'bob@example.com']);

        $nameResults = $this->userService->searchUsers('John');
        $emailResults = $this->userService->searchUsers('example.com');

        expect($nameResults->count())->toBe(1);
        expect($nameResults->first()->name)->toBe('John Doe');

        expect($emailResults->count())->toBe(2);
    });

    it('validates subscription expiry for customers', function () {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'subscription_expires' => now()->addDays(30),
        ]);

        $result = $this->userService->checkSubscriptionStatus($customer->id);

        expect($result['active'])->toBeTrue();
        expect($result['days_remaining'])->toBeGreaterThan(25);
    });

    it('detects expired subscriptions', function () {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'subscription_expires' => now()->subDays(5),
        ]);

        $result = $this->userService->checkSubscriptionStatus($customer->id);

        expect($result['active'])->toBeFalse();
        expect($result['days_remaining'])->toBeLessThan(0);
    });
});
