<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserService
{
    public function createUser(array $data): array
    {
        try {
            // Validate the data
            $validator = Validator::make($data, User::getValidationRules());

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->toArray(),
                ];
            }

            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Set defaults based on role
            $data = $this->setRoleDefaults($data);

            // Create user
            $user = User::create($data);

            Log::info("User created: {$user->email} with role {$user->role}");

            return [
                'success' => true,
                'message' => 'User created successfully',
                'user' => $user,
            ];

        } catch (\Exception $e) {
            Log::error('Error creating user: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'An error occurred while creating the user',
            ];
        }
    }

    public function updateUser(User $user, array $data): array
    {
        try {
            // Validate the data
            $validator = Validator::make($data, User::getValidationRules($user->id));

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->toArray(),
                ];
            }

            // Hash password if provided
            if (isset($data['password']) && ! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            // Set defaults based on role if role changed
            if (isset($data['role']) && $data['role'] !== $user->role) {
                $data = $this->setRoleDefaults($data);
            }

            // Update user
            $user->update($data);

            Log::info("User updated: {$user->email}");

            return [
                'success' => true,
                'message' => 'User updated successfully',
                'user' => $user,
            ];

        } catch (\Exception $e) {
            Log::error("Error updating user {$user->id}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => 'An error occurred while updating the user',
            ];
        }
    }

    public function deleteUser(User $user): array
    {
        try {
            // Prevent deletion of the last admin
            if ($user->isAdmin() && User::admins()->count() <= 1) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete the last administrator user',
                ];
            }

            $email = $user->email;
            $user->delete();

            Log::info("User deleted: {$email}");

            return [
                'success' => true,
                'message' => 'User deleted successfully',
            ];

        } catch (\Exception $e) {
            Log::error("Error deleting user {$user->id}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => 'An error occurred while deleting the user',
            ];
        }
    }

    public function toggleUserStatus(User $user): array
    {
        try {
            // Prevent deactivating the last active admin
            if ($user->isAdmin() && $user->active && User::admins()->active()->count() <= 1) {
                return [
                    'success' => false,
                    'message' => 'Cannot deactivate the last active administrator',
                ];
            }

            $user->update(['active' => ! $user->active]);

            $status = $user->active ? 'activated' : 'deactivated';
            Log::info("User {$status}: {$user->email}");

            return [
                'success' => true,
                'message' => "User {$status} successfully",
            ];

        } catch (\Exception $e) {
            Log::error("Error toggling user status {$user->id}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => 'An error occurred while updating user status',
            ];
        }
    }

    public function resetPassword(User $user, ?string $newPassword = null): array
    {
        try {
            // Generate password if not provided
            if (! $newPassword) {
                $newPassword = $this->generatePassword();
            }

            $user->update(['password' => Hash::make($newPassword)]);

            Log::info("Password reset for user: {$user->email}");

            return [
                'success' => true,
                'message' => 'Password reset successfully',
                'password' => $newPassword,
            ];

        } catch (\Exception $e) {
            Log::error("Error resetting password for user {$user->id}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => 'An error occurred while resetting the password',
            ];
        }
    }

    public function getUsersByRole(string $role): Collection
    {
        return User::withRole($role)->orderBy('name')->get();
    }

    public function getActiveUsers(): Collection
    {
        return User::active()->orderBy('name')->get();
    }

    public function getUserStats(): array
    {
        return [
            'total' => User::count(),
            'active' => User::active()->count(),
            'inactive' => User::where('active', false)->count(),
            'admins' => User::admins()->count(),
            'agents' => User::agents()->count(),
            'customers' => User::customers()->count(),
            'customers_with_active_subscription' => User::customers()->whereDate('subscription_expires', '>', now())->count(),
        ];
    }

    public function searchUsers(string $query): Collection
    {
        return User::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('first_name', 'like', "%{$query}%")
                ->orWhere('last_name', 'like', "%{$query}%")
                ->orWhere('company', 'like', "%{$query}%");
        })->orderBy('name')->get();
    }

    public function generatePassword(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $password;
    }

    private function setRoleDefaults(array $data): array
    {
        $role = $data['role'] ?? null;

        switch ($role) {
            case User::ROLE_ADMIN:
                $data['active'] = true; // Admins are always active by default
                break;

            case User::ROLE_AGENT:
                $data['active'] = $data['active'] ?? true;
                break;

            case User::ROLE_CUSTOMER:
                $data['active'] = $data['active'] ?? true;
                $data['customer_type'] = $data['customer_type'] ?? User::CUSTOMER_TYPE_INDIVIDUAL;
                $data['account_balance'] = $data['account_balance'] ?? 0.00;
                break;
        }

        return $data;
    }

    public function validateUserAccess(User $user, ?string $requiredRole = null): bool
    {
        // Check if user is active
        if (! $user->active) {
            return false;
        }

        // Check role requirement
        if ($requiredRole) {
            switch ($requiredRole) {
                case 'admin':
                    return $user->isAdmin();
                case 'agent':
                    return $user->isAgent() || $user->isAdmin();
                case 'customer':
                    return $user->isCustomer();
                default:
                    return false;
            }
        }

        return true;
    }

    public function getCustomersWithExpiringSoon(int $days = 30): Collection
    {
        return User::customers()
            ->whereDate('subscription_expires', '<=', now()->addDays($days))
            ->whereDate('subscription_expires', '>', now())
            ->orderBy('subscription_expires')
            ->get();
    }

    public function extendSubscription(User $user, int $days): array
    {
        try {
            if (! $user->isCustomer()) {
                return [
                    'success' => false,
                    'message' => 'User is not a customer',
                ];
            }

            $currentExpiry = $user->subscription_expires ?: now();
            $newExpiry = $currentExpiry->addDays($days);

            $user->update(['subscription_expires' => $newExpiry]);

            Log::info("Subscription extended for user {$user->email} until {$newExpiry->format('Y-m-d')}");

            return [
                'success' => true,
                'message' => "Subscription extended until {$newExpiry->format('Y-m-d')}",
                'expires_at' => $newExpiry,
            ];

        } catch (\Exception $e) {
            Log::error("Error extending subscription for user {$user->id}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => 'An error occurred while extending the subscription',
            ];
        }
    }

    public function activateUser($userId): array
    {
        try {
            $user = User::find($userId);
            if (! $user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            $user->update(['active' => true]);

            return ['success' => true, 'message' => 'User activated successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error activating user'];
        }
    }

    public function deactivateUser($userId): array
    {
        try {
            $user = User::find($userId);
            if (! $user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            $user->update(['active' => false]);

            return ['success' => true, 'message' => 'User deactivated successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error deactivating user'];
        }
    }

    public function changeUserRole($userId, string $role): array
    {
        try {
            if (! in_array($role, array_keys(User::getRoles()))) {
                return ['success' => false, 'message' => 'Invalid role provided'];
            }

            $user = User::find($userId);
            if (! $user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            $user->update(['role' => $role]);

            return ['success' => true, 'message' => 'User role changed successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error changing user role'];
        }
    }

    public function getUserStatistics(): array
    {
        return [
            'total' => User::count(),
            'active' => User::active()->count(),
            'inactive' => User::where('active', false)->count(),
            'by_role' => [
                User::ROLE_ADMIN => User::admins()->count(),
                User::ROLE_AGENT => User::agents()->count(),
                User::ROLE_CUSTOMER => User::customers()->count(),
            ],
        ];
    }

    public function bulkActivateUsers(array $userIds): array
    {
        try {
            $count = User::whereIn('id', $userIds)->update(['active' => true]);

            return ['success' => true, 'count' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error activating users'];
        }
    }

    public function bulkDeactivateUsers(array $userIds): array
    {
        try {
            $count = User::whereIn('id', $userIds)->update(['active' => false]);

            return ['success' => true, 'count' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error deactivating users'];
        }
    }

    public function checkSubscriptionStatus($userId): array
    {
        try {
            $user = User::find($userId);
            if (! $user || ! $user->isCustomer()) {
                return ['active' => false, 'days_remaining' => 0];
            }

            if (! $user->subscription_expires) {
                return ['active' => false, 'days_remaining' => 0];
            }

            $daysRemaining = now()->diffInDays($user->subscription_expires, false);

            return [
                'active' => $daysRemaining > 0,
                'days_remaining' => $daysRemaining,
            ];
        } catch (\Exception $e) {
            return ['active' => false, 'days_remaining' => 0];
        }
    }
}
