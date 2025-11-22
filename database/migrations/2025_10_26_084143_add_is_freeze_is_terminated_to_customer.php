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
        Schema::table('beat_customers', function (Blueprint $table) {
            $table->boolean('is_frozen')->default(false);
            $table->boolean('is_terminated')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beat_customers', function (Blueprint $table) {
            $table->dropColumn('is_frozen');
            $table->dropColumn('is_terminated');
        });
    }
};
