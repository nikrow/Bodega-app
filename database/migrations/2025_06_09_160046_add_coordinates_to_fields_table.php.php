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
        Schema::table('fields', function (Blueprint $table) {
            // Agregar columnas de coordenadas
            $table->decimal('latitude', 10, 8)->nullable()->after('api_key');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            // Eliminar columnas de coordenadas
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
