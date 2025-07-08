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
        Schema::create('resource_configs', function (Blueprint $table) {
            $table->id();
            $table->string('resource');
            $table->string('navigation_group')->default('');
            $table->string('navigation_icon')->nullable()->default('heroicon-o-rectangle-stack');
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_configs');
    }
};
