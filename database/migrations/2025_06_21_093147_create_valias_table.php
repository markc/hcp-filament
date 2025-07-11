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
        Schema::create('valias', function (Blueprint $table) {
            $table->id();
            $table->string('source')->unique(); // alias email or @domain.com for catchall
            $table->text('target'); // comma-separated target emails
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('source');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valias');
    }
};
