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
        Schema::create('vmails', function (Blueprint $table) {
            $table->id();
            $table->string('user', 191)->unique();
            $table->integer('gid')->default(1000);
            $table->integer('uid')->default(1000);
            $table->boolean('active')->default(true);
            $table->longText('clearpw');
            $table->string('password', 191);
            $table->string('home', 191);
            $table->timestamps();

            $table->index('user');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vmails');
    }
};
