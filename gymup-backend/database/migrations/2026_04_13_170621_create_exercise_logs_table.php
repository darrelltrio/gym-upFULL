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
    Schema::create('exercise_logs', function (Blueprint $table) {
        $table->id();
        
        // Relasi ke Sesi Latihan dan Jenis Latihannya
        $table->foreignId('workout_session_id')->constrained('workout_sessions')->cascadeOnDelete();
        $table->foreignId('exercise_id')->constrained('exercises')->restrictOnDelete();
        
        $table->integer('set_number');
        $table->decimal('weight_kg', 5, 2); // 5 digit total, 2 di belakang koma (misal: 120.50)
        $table->integer('reps');
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_logs');
    }
};
