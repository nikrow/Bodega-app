<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockMovementsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_type'); // 'entrada', 'salida', 'traslado', 'application_usage'
            $table->unsignedBigInteger('field_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id')->nullable(); // Puede ser origen o destino
            $table->unsignedBigInteger('related_id'); // ID del movimiento original
            $table->string('related_type'); // Clase del modelo original (Movimiento, OrderApplicationUsage)
            $table->integer('quantity_change'); // Positivo para entradas, negativo para salidas
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_id'); // Usuario que realizó el movimiento
            $table->timestamps();

            // Índices y claves foráneas
            $table->foreign('field_id')->references('id')->on('fields')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Índice para consultas rápidas
            $table->index(['related_id', 'related_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
}
