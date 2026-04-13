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
    Schema::create('workout_sessions', function (Blueprint $table) {
        $table->id();
        
        // Relasi ke User yang melakukan latihan
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        
        $table->timestamp('start_time')->useCurrent();
        $table->timestamp('end_time')->nullable();
        $table->integer('duration_minutes')->nullable();
        
        // Metrik Hasil Latihan
        $table->bigInteger('total_volume')->default(0);
        $table->integer('session_xp')->default(0);
        
        // Fitur B2B2C Scale-Up: RPE (Rate of Perceived Exertion)
        $table->integer('rpe_score')->nullable()->comment('Skala 1-10 dari user setelah latihan');
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_sessions');
    }
};
