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
    Schema::create('gyms', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        
        // Status untuk keperluan SaaS (Super Admin bisa mematikan akses gym jika telat bayar)
        $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
        $table->date('subscription_ends_at')->nullable();
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gyms');
    }
};
