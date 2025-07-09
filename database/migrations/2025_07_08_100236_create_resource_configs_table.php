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
            $table->id(); // BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->string('resource', 191)->collation('utf8mb4_unicode_ci'); // VARCHAR(191) NOT NULL
            $table->string('navigation_group', 191)->nullable()->default('')->collation('utf8mb4_unicode_ci'); // VARCHAR(191) NULL DEFAULT ''
            $table->string('navigation_icon', 191)->nullable()->default('heroicon-o-rectangle-stack')->collation('utf8mb4_unicode_ci'); // VARCHAR(191) NULL DEFAULT 'heroicon-o-rectangle-stack'
            $table->string('navigation_label', 191)->nullable()->collation('utf8mb4_unicode_ci'); // VARCHAR(191) NULL DEFAULT NULL
            $table->timestamps(); // created_at und updated_at TIMESTAMP NULL DEFAULT NULL
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
