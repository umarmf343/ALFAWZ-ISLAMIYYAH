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
        Schema::table('quran_progress', function (Blueprint $table) {
            $table->float('memorized_confidence')->default(0)->after('hasanat'); // 0-1 confidence score
            $table->integer('memorization_reviews')->default(0)->after('memorized_confidence'); // Number of memorization reviews
            
            // Add index for memorization queries
            $table->index('memorized_confidence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_progress', function (Blueprint $table) {
            $table->dropIndex(['memorized_confidence']);
            $table->dropColumn(['memorized_confidence', 'memorization_reviews']);
        });
    }
};