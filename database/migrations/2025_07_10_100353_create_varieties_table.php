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
        Schema::create('varieties', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Nombre de la variedad, debería ser único
            $table->foreignId('crop_id')
                ->constrained('crops') // Relación con la tabla 'crops'
                ->onDelete('cascade'); // Eliminar variedades si se elimina el cultivo
            $table->string('description')->nullable(); // Descripción opcional de la variedad
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('varieties');
    }
};