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
        Schema::create('beat_customers', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('middlename')->nullable();
            $table->integer('gender'); // 0: female, 1: male, 2: other
            $table->date('birthdate')->nullable();
            $table->integer('age')->nullable();
            $table->string('a_region')->nullable();
            $table->string('a_province')->nullable();
            $table->string('a_city')->nullable();
            $table->string('a_barangay')->nullable();
            $table->string('a_street')->nullable();
            $table->string('a_zipcode')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('phone2')->nullable();
            $table->string('keypab')->unique()->nullable();
            $table->unsignedBigInteger('membership_id');
            $table->date('membership_start')->nullable();
            $table->date('membership_end')->nullable();
            $table->integer('status')->default(1); // 0: inactive, 1: active
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beat_customers');
    }
};
