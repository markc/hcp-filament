<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_AGENT = 'agent';

    public const ROLE_CUSTOMER = 'customer';

    public const CUSTOMER_TYPE_INDIVIDUAL = 'individual';

    public const CUSTOMER_TYPE_BUSINESS = 'business';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'password',
        'role',
        'active',
        'phone',
        'company',
        'department',
        'customer_type',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'account_balance',
        'subscription_expires',
        'notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'account_balance' => 'decimal:2',
            'subscription_expires' => 'date',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include users with a specific role.
     */
    public function scopeWithRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * Scope a query to only include admin users.
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    /**
     * Scope a query to only include agent users.
     */
    public function scopeAgents(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_AGENT);
    }

    /**
     * Scope a query to only include customer users.
     */
    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_CUSTOMER);
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is an agent.
     */
    public function isAgent(): bool
    {
        return $this->role === self::ROLE_AGENT;
    }

    /**
     * Check if user is a customer.
     */
    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return "{$this->first_name} {$this->last_name}";
        }

        if ($this->first_name) {
            return $this->first_name;
        }

        if ($this->last_name) {
            return $this->last_name;
        }

        return $this->name ?? '';
    }

    /**
     * Get the user's display name for admin purposes.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->full_name;

        if ($this->company) {
            $name .= " ({$this->company})";
        }

        return $name;
    }

    /**
     * Check if subscription is active (for customers).
     */
    public function hasActiveSubscription(): bool
    {
        if (! $this->isCustomer()) {
            return true;
        }

        return $this->subscription_expires && $this->subscription_expires->isFuture();
    }

    /**
     * Get available roles.
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_AGENT => 'Agent',
            self::ROLE_CUSTOMER => 'Customer',
        ];
    }

    /**
     * Get available customer types.
     */
    public static function getCustomerTypes(): array
    {
        return [
            self::CUSTOMER_TYPE_INDIVIDUAL => 'Individual',
            self::CUSTOMER_TYPE_BUSINESS => 'Business',
        ];
    }

    /**
     * Get role color for UI display.
     */
    public function getRoleColorAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'danger',
            self::ROLE_AGENT => 'warning',
            self::ROLE_CUSTOMER => 'success',
            default => 'gray',
        };
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Only admins and agents can access the admin panel
        return $this->active && in_array($this->role, [self::ROLE_ADMIN, self::ROLE_AGENT]);
    }

    /**
     * Get validation rules for user creation/update.
     */
    public static function getValidationRules(?int $userId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'.($userId ? ",{$userId}" : '')],
            'password' => $userId ? ['nullable', 'min:8'] : ['required', 'min:8'],
            'role' => ['required', 'in:'.implode(',', array_keys(self::getRoles()))],
            'active' => ['boolean'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'customer_type' => ['nullable', 'in:'.implode(',', array_keys(self::getCustomerTypes()))],
            'account_balance' => ['nullable', 'numeric', 'min:0'],
            'subscription_expires' => ['nullable', 'date', 'after:today'],
        ];
    }
}
