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
    Schema::table('exercises', function (Blueprint $table) {
        // Jika NULL, berarti latihan global dari Super Admin
        $table->foreignId('created_by')->after('id')->nullable()->constrained('users')->nullOnDelete();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            //
        });
    }
};
