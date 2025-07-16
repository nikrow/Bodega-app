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
        Schema::table('movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_order_id')->nullable();
        });

        Schema::table('movimientos', function (Blueprint $table) {
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade')->nullOnDelete();
        });
    

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');

        });
    }
};
