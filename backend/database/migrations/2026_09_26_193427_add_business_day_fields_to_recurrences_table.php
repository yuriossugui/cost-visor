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
        Schema::table('recurrences', function (Blueprint $table) {
            $table->boolean('is_business_day')->default(false);
            $table->unsignedTinyInteger('business_day_number')->nullable(); // 1st, 2nd, 3rd, 4th, 5th business day
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurrences', function (Blueprint $table) {
            $table->dropColumn(['is_business_day', 'business_day_number']);
        });
    }
};