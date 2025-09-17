<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Create payments table for Paystack transaction tracking.
     * Stores payment references, amounts, and status for subscription management.
     */
    public function up(): void {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('paystack_reference')->unique();
            $table->string('paystack_access_code')->nullable();
            $table->unsignedInteger('amount_kobo'); // amount in kobo (NGN cents)
            $table->string('currency', 3)->default('NGN');
            $table->enum('status', ['pending','success','failed','cancelled'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('payments');
    }
};