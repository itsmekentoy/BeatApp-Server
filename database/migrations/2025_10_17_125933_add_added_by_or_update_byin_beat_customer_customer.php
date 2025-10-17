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
            $table->text('medical_history')->nullable()->after('status');
        });
        // drop the table beat_customer_medicals
        Schema::dropIfExists('beat_customer_medicals');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beat_customers', function (Blueprint $table) {
            $table->dropColumn('medical_history');
        });
        Schema::create('beat_customer_medicals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beat_customer_id');
            $table->string('medical_condition')->nullable();
            $table->timestamps();
        });
    }
};
