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
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('movement_number')->unique()->index();
            $table->enum('tipo', ['entrada', 'salida', 'traslado']);
            $table->float('comprobante')->nullable();
            $table->string('encargado')->nullable();
            $table->foreignId('field_id')->constrained('fields')->cascadeOnDelete();
            $table->foreignId('bodega_origen_id')->nullable()->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('bodega_destino_id')->nullable()->constrained('warehouses')->cascadeOnDelete();
            $table->string('orden_compra')->nullable();
            $table->string('nombre_proveedor')->nullable();
            $table->string('guia_despacho')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
