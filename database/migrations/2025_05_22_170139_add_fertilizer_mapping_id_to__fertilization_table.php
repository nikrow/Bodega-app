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
        Schema::table('fertilizations', function (Blueprint $table) {
            $table->foreignId('fertilizer_mapping_id')
                ->nullable()
                ->after('product_id')
                ->constrained('fertilizer_mappings')
                ->nullOnDelete()
                ->cascadeOnUpdate()
                ->comment('ID del mapeo de fertilizantes');
            $table->decimal('product_price', 10, 2)
                ->nullable()
                ->after('quantity_product')
                ->comment('Precio del producto');
            $table->decimal('total_cost', 10, 2)
                ->nullable()
                ->after('product_price')
                ->comment('Costo de fertilización');
            $table->string('application_method')
                ->nullable()
                ->after('total_cost')
                ->comment('Método de aplicación');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fertilizations', function (Blueprint $table) {
            $table->dropForeign(['fertilizer_mapping_id']);
            $table->dropColumn('fertilizer_mapping_id');
            $table->dropColumn('product_price');
            $table->dropColumn('total_cost');
        });
    }
};
