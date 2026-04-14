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
    Schema::table('gyms', function (Blueprint $table) {
        $table->string('owner_name')->after('name')->nullable();
        $table->text('address')->after('owner_name')->nullable();
        $table->string('logo_url')->after('address')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            //
        });
    }
};
