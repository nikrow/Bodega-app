<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_ins', function (Blueprint $table) {
            $table->foreignId('movimiento_id')->constrained()->onDelete('cascade');
            $table->foreignId('movimiento_producto_id')->nullable()->constrained('movimiento_productos')->onDelete('set null');
            $table->foreignId('purchase_order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->string('provider_name')->nullable();
            $table->string('guia_despacho')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->constrained('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_ins', function (Blueprint $table) {
            $table->dropForeign(['movimiento_id']);
            $table->dropForeign(['movimiento_producto_id']);
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn([
                'movimiento_id',
                'movimiento_producto_id',
                'purchase_order_id',
                'product_id',
                'quantity',
                'provider_name',
                'guia_despacho',
                'created_by',
                'updated_by'
            ]);
        });
    }
};