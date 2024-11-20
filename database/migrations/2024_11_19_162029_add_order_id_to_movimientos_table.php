<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->after('id');

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });
    }

};
