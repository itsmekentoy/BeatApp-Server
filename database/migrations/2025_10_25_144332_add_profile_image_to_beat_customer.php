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
            $table->string('profile_picture')->nullable()->after('keypab');
            $table->dropColumn('phone2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beat_customers', function (Blueprint $table) {
            $table->dropColumn('profile_picture');
            $table->string('phone2')->nullable()->after('phone');
        });
    }
};
