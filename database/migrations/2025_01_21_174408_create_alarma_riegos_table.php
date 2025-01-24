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
        Schema::create('alarma_riegos', function (Blueprint $table) {
            $table->id();
            $table->string('programa_irrigacion');
            $table->foreignId('parcel_id')->constrained('parcels');
            $table->string('cuartel');
            $table->string('alarma_tipo');
            $table->integer('caudal');
            $table->integer('esperado');
            $table->decimal('diferencia_porcentaje', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarma_riegos');
    }
};
