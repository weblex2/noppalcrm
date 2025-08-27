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
        Schema::table('resource_configs', function (Blueprint $table) {
            $table->boolean('is_wizard')->default(0)->after('navigation_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resource_configs', function (Blueprint $table) {
            $table->dropColumn('is_wizard');
        });
    }
};
