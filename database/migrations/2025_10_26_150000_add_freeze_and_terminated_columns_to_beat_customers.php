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
            if (! Schema::hasColumn('beat_customers', 'is_frozen')) {
                $table->boolean('is_frozen')->default(false)->after('status');
            }
            if (! Schema::hasColumn('beat_customers', 'is_terminated')) {
                $table->boolean('is_terminated')->default(false)->after('is_frozen');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beat_customers', function (Blueprint $table) {
            if (Schema::hasColumn('beat_customers', 'is_frozen')) {
                $table->dropColumn('is_frozen');
            }
            if (Schema::hasColumn('beat_customers', 'is_terminated')) {
                $table->dropColumn('is_terminated');
            }
        });
    }
};
