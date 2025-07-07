<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('field')->unique();  // Das Feld für den Schlüssel, der eindeutig ist
            $table->text('value')->nullable();  // Das Feld für den Wert, der gespeichert wird
            $table->timestamps();  // Automatisch erstellte Zeitstempel für created_at und updated_at
        });
    }

    /**
     * Rückgängig machen der Migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('general_settings');
    }
};
