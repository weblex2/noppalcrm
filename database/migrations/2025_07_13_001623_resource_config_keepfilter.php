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
            $table->boolean('keep_filter')->after('navigation_label')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropColumn('keep_filter');
    }
};
