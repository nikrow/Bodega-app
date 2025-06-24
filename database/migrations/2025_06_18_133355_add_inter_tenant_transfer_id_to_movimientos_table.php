<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('inter_tenant_transfer_id')->nullable()->after('user_id');
            $table->foreign('inter_tenant_transfer_id')->references('id')->on('inter_tenant_transfers');
        });
    }

    public function down()
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropForeign(['inter_tenant_transfer_id']);
            $table->dropColumn('inter_tenant_transfer_id');
        });
    }
};