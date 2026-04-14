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
    Schema::create('gym_exercise_inventory', function (Blueprint $table) {
        $table->id();
        $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
        $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_exercise_inventory');
    }
};
