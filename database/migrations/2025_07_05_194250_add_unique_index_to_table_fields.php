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
        Schema::table('table_fields', function (Blueprint $table) {
        $table->string('form', 1)->change();
        $table->string('table', 100)->change();
        $table->string('field', 100)->change();
            #$table->unique(['form', 'table', 'field'], 'table_fields_form_table_field_unique');
        });
    }

    public function down(): void
    {
        Schema::table('table_fields', function (Blueprint $table) {
            $table->dropUnique('table_fields_form_table_field_unique');
        });
    }
};
