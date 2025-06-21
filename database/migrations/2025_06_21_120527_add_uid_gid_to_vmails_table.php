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
        Schema::table('vmails', function (Blueprint $table) {
            if (! Schema::hasColumn('vmails', 'uid')) {
                $table->integer('uid')->after('password');
            }
            if (! Schema::hasColumn('vmails', 'gid')) {
                $table->integer('gid')->after('uid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vmails', function (Blueprint $table) {
            $table->dropColumn(['uid', 'gid']);
        });
    }
};
