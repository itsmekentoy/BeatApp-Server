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
        Schema::table('last_tapp_key_pobs', function (Blueprint $table) {
            $table->dropUnique('last_tapp_key_pobs_keyfob_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('last_tapp_key_pobs', function (Blueprint $table) {
            $table->unique('keyfob_number');
        });
    }
};
