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
    Schema::create('exercises', function (Blueprint $table) {
        $table->id(); // Ini otomatis membuat kolom 'id' sebagai Primary Key
        
        $table->string('name');
        // Kita gunakan enum agar data bersih dan mudah untuk logic Chart RPG nanti
        $table->enum('target_muscle', ['chest', 'back', 'legs', 'shoulders', 'arms', 'core', 'full_body']);
        $table->enum('type', ['compound', 'isolation', 'cardio']);
        
        $table->integer('base_xp')->default(10);
        $table->string('asset_url')->nullable(); // Untuk gambar/GIF latihan
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
