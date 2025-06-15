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
        Schema::create('table_fields', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('section')->default(1);
            $table->tinyInteger('form')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('label', 191)->nullable();
            $table->string('table', 191)->nullable();
            $table->string('field', 191)->nullable();
            $table->string('type', 191)->nullable();
            $table->tinyInteger('is_badge')->nullable();
            $table->longText('badge_color')->nullable();
            $table->string('align', 191)->nullable();
            $table->string('select_options', 191)->nullable();
            $table->longText('visible')->nullable();
            $table->string('format', 191)->nullable();
            $table->string('relation_table', 191)->nullable();
            $table->string('relation_show_field', 191)->nullable();
            $table->longText('extra_attributes')->nullable();
            $table->string('color', 191)->nullable();
            $table->tinyInteger('searchable')->default(0);
            $table->tinyInteger('sortable')->default(0);
            $table->tinyInteger('is_togglable')->default(0);
            $table->tinyInteger('disabled')->default(0);
            $table->tinyInteger('required')->default(0);
            $table->tinyInteger('dehydrated')->default(0);
            $table->tinyInteger('collapsible')->default(0);
            $table->integer('colspan')->nullable();
            $table->string('icon', 191)->nullable();
            $table->string('icon_color', 191)->nullable();
            $table->string('link', 191)->nullable();
            $table->string('link_target', 191)->nullable();
            $table->string('badgecolor', 191)->nullable();
            $table->integer('order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_fields');
    }
};
