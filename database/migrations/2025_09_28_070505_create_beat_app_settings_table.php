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
        Schema::create('beat_app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('Beat App');
            $table->string('app_version')->default('1.0.0');
            $table->string('support_email')->default('support@beatapp.com');
            $table->string('support_phone')->nullable();
            $table->string('default_language')->default('en');
            $table->string('timezone')->default('UTC');
            $table->boolean('maintenance_mode')->default(false);
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beat_app_settings');
    }
};
