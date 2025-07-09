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
            $table->unique('resource', 'resource_configs_resource_unique');
        });
    }

    public function down(): void
    {
        Schema::table('resource_configs', function (Blueprint $table) {
            $table->dropUnique('resource_configs_resource_unique');
        });
    }
};
