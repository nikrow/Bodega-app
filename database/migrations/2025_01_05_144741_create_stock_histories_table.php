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
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('field_id')->constrained('fields');
            $table->decimal('quantity_snapshot', 15, 4);
            $table->decimal('price_snapshot', 15, 4);
            $table->enum('movement_type', ['entrada', 'salida', 'traslado', 'preparacion', 'traslado-entrada', 'traslado-salida']);
            $table->foreignId('movement_id');
            $table->foreignId('movement_product_id');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};
