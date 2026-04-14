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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete(); // Este es el "Lugar"
            $table->string('name');
            $table->text('description')->nullable(); // Descripción del evento
            $table->string('poster_url')->nullable(); // URL o ruta de la imagen/poster
            $table->dateTime('start_time'); // Inicio
            $table->dateTime('end_time')->nullable(); // Cierre
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
