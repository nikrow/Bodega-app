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
        Schema::table('order_application_usages', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->after('product_usage')->nullable();
            $table->decimal('total_cost', 10, 2)->after('price')->nullable();
        });
    }

    public function down()
    {
        Schema::table('order_application_usages', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->dropColumn('total_cost');
        });
    }

};
