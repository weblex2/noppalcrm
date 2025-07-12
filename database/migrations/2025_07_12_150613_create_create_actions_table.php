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
        Schema::create('filament_actions', function (Blueprint $table) {
            $table->id();
            $table->string('resource');
            $table->string('action_name');
            $table->string('label');
            $table->string('type')->default('header');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('view')->nullable();
            $table->boolean('modal_submit_action')->default(false);
            $table->boolean('modal_cancel_action')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filament_actions');
    }
};
