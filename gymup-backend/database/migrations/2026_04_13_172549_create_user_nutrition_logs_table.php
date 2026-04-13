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
    Schema::create('user_nutrition_logs', function (Blueprint $table) {
        $table->id();
        
        // Relasi (Log ini milik siapa, dan makanan apa yang dimakan)
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('food_id')->constrained('foods')->restrictOnDelete();
        
        $table->date('date');
        $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snack']);
        $table->decimal('quantity', 5, 2)->default(1)->comment('Pengali dari serving_size');
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_nutrition_logs');
    }
};
