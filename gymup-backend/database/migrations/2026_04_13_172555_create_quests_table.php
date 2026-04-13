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
    Schema::create('quests', function (Blueprint $table) {
        $table->id();
        
        // Null = Global Quest, Not Null = Custom Gym Quest
        $table->foreignId('gym_id')->nullable()->constrained('gyms')->cascadeOnDelete();
        
        $table->string('title');
        $table->text('description')->nullable();
        
        // Jenis target (misal: 'total_volume', 'login_streak', 'cardio_duration')
        $table->string('requirement_type');
        $table->integer('target_value');
        $table->integer('xp_reward');
        $table->boolean('is_daily')->default(true);
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quests');
    }
};
