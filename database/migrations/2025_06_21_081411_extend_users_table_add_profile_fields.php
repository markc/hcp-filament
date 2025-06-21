<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Role and status fields
            $table->enum('role', ['admin', 'agent', 'customer'])->default('customer')->after('email');
            $table->boolean('active')->default(true)->after('role');

            // Profile fields
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('last_name');
            $table->string('company')->nullable()->after('phone');
            $table->text('address')->nullable()->after('company');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country')->nullable()->after('postal_code');

            // Admin/Agent specific fields
            $table->string('department')->nullable()->after('country');
            $table->text('notes')->nullable()->after('department');

            // Customer specific fields
            $table->string('customer_type')->nullable()->after('notes'); // individual, business
            $table->decimal('account_balance', 10, 2)->default(0)->after('customer_type');
            $table->date('subscription_expires')->nullable()->after('account_balance');

            // Timestamps
            $table->timestamp('last_login_at')->nullable()->after('subscription_expires');

            // Indexes
            $table->index('role');
            $table->index('active');
            $table->index(['role', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'active',
                'first_name',
                'last_name',
                'phone',
                'company',
                'address',
                'city',
                'state',
                'postal_code',
                'country',
                'department',
                'notes',
                'customer_type',
                'account_balance',
                'subscription_expires',
                'last_login_at',
            ]);
        });
    }
};
