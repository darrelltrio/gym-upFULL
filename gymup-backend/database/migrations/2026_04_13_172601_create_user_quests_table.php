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
    Schema::create('user_quests', function (Blueprint $table) {
        $table->id();
        
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('quest_id')->constrained()->cascadeOnDelete();
        
        $table->integer('current_progress')->default(0)->comment('Nilai progres berjalan');
        $table->enum('status', ['in_progress', 'completed', 'claimed'])->default('in_progress');
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_quests');
    }
};
