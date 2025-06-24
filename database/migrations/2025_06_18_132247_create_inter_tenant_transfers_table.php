<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inter_tenant_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_origen_id');
            $table->unsignedBigInteger('tenant_destino_id');
            $table->unsignedBigInteger('bodega_origen_id');
            $table->unsignedBigInteger('bodega_destino_id');
            $table->unsignedBigInteger('movimiento_origen_id')->nullable();
            $table->unsignedBigInteger('movimiento_destino_id')->nullable();
            $table->unsignedBigInteger('orden_compra_id')->nullable();
            $table->string('estado')->default('pendiente');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            // Claves foráneas
            $table->foreign('tenant_origen_id')->references('id')->on('fields');
            $table->foreign('tenant_destino_id')->references('id')->on('fields');
            $table->foreign('bodega_origen_id')->references('id')->on('warehouses');
            $table->foreign('bodega_destino_id')->references('id')->on('warehouses');
            $table->foreign('movimiento_origen_id')->references('id')->on('movimientos');
            $table->foreign('movimiento_destino_id')->references('id')->on('movimientos');
            $table->foreign('orden_compra_id')->references('id')->on('purchase_orders');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inter_tenant_transfers');
    }
};