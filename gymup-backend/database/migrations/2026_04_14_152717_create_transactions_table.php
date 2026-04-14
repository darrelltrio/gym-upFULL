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
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Member yang bayar
        
        $table->string('reference_number')->unique(); // Contoh: INV-20231010-001
        $table->enum('type', ['membership_extension', 'product_sale', 'other']);
        $table->decimal('amount', 12, 2); // Mendukung nominal besar hingga ratusan juta
        $table->string('description'); // Contoh: "Perpanjangan Member 1 Bulan" atau "Beli Air Mineral"
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
