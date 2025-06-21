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
            $table->string('user')->unique(); // email address
            $table->string('password');
            $table->integer('quota')->default(1024); // MB
            $table->string('home')->nullable();
            $table->boolean('active')->default(true);
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
