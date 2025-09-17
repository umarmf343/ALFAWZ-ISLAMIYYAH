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
            $table->string('phone')->nullable()->after('email');
            $table->text('bio')->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('bio');
            $table->enum('gender', ['male', 'female'])->nullable()->after('date_of_birth');
            $table->string('country')->nullable()->after('gender');
            $table->string('timezone')->default('UTC')->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'bio', 'date_of_birth', 'gender', 'country', 'timezone']);
        });
    }
};
