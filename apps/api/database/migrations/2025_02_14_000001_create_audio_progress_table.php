<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audio_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('surah_id');
            $table->string('surah_name');
            $table->double('position_seconds')->unsigned()->default(0);
            $table->double('duration_seconds')->unsigned()->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'surah_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_progress');
    }
};
