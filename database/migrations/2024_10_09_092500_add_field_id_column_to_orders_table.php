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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('field_id')->constrained('fields')->onDelete('cascade');
            $table->foreignId('crops_id')->constrained('fields')->onDelete('cascade');
            $table->string('Equipment');
            $table->string('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_field_id_foreign');
            $table->dropForeign('orders_crops_id_foreign');
        });
    }
};
