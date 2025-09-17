<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create plans table for tuition and subscription management.
 * Defines pricing tiers with intervals and perks.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., 'basic_monthly', 'premium_yearly'
            $table->string('name'); // display name
            $table->decimal('amount', 10, 2); // price in smallest currency unit
            $table->enum('interval', ['monthly', 'term', 'yearly']);
            $table->string('currency', 3)->default('NGN'); // ISO currency code
            $table->json('perks_json')->nullable(); // features included in plan
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Indexes for plan queries
            $table->index('code');
            $table->index(['active', 'interval']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};