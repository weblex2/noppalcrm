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
            $table->integer('section_nr')->nullable(0)->after('field');
            $table->string('section_name')->nullable(0)->after('section_nr');
            $table->boolean('is_repeater')->nullable(0)->after('section_name');
            $table->string('repeats_resource')->nullable(0)->after('is_repeater');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resource_configs', function (Blueprint $table) {
            $table->dropColumn('section_nr');
            $table->dropColumn('section_name');
            $table->dropColumn('is_repeater');
            $table->dropColumn('repeats_resource');
        });
    }
};
