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
            $table->dropColumn([
                'a_region',
                'a_province',
                'a_city',
                'a_barangay',
                'a_street',
                'a_zipcode'
            ]);
            $table->string('address')->nullable()->after('age');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beat_customers', function (Blueprint $table) {
            $table->string('a_region')->nullable()->after('age');
            $table->string('a_province')->nullable()->after('a_region');
            $table->string('a_city')->nullable()->after('a_province');
            $table->string('a_barangay')->nullable()->after('a_city');
            $table->string('a_street')->nullable()->after('a_barangay');
            $table->string('a_zipcode')->nullable()->after('a_street');
            $table->dropColumn('address');
        });
    }
};
