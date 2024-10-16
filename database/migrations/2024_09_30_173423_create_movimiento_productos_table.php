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
        Schema::create('movimiento_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_id')->constrained('movimientos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('precio_compra', 13, 1)->nullable();  // 10 dígitos en total, 2 decimales
            $table->enum('unidad_medida', ['kilogramo', 'litro', 'unidad']);
            $table->unsignedSmallInteger('cantidad');
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
        Schema::dropIfExists('movimiento_productos');
    }
};
