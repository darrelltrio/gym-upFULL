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
    Schema::create('foods', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        
        // Makronutrisi per serving
        $table->integer('calories');
        $table->decimal('protein', 5, 2);
        $table->decimal('carbs', 5, 2);
        $table->decimal('fats', 5, 2);
        
        $table->string('serving_size')->comment('Contoh: 100g, 1 mangkok, 1 scoop');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
