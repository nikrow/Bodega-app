<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_destino_id')->nullable()->after('field_id');
            $table->foreign('tenant_destino_id')->references('id')->on('fields');
        });
    }

    public function down()
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropForeign(['tenant_destino_id']);
            $table->dropColumn('tenant_destino_id');
        });
    }
};