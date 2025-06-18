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
        Schema::create('model_relationships', function (Blueprint $table) {
            $table->id();
            $table->string('source_model'); // z. B. App\Models\Company
            $table->string('related_model'); // z. B. App\Models\Contact
            $table->string('relationship_type'); // z. B. hasMany, belongsTo, belongsToMany
            $table->string('foreign_key')->nullable(); // z. B. company_id
            $table->string('pivot_table')->nullable(); // Für belongsToMany
            $table->string('foreign_pivot_key')->nullable(); // Für belongsToMany
            $table->string('related_pivot_key')->nullable(); // Für belongsToMany
            $table->string('method_name'); // z. B. contacts
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_relationships');
    }
};
