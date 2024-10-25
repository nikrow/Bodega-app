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
        Schema::table('movimiento_productos', function (Blueprint $table) {
            $table->string('lot_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->foreignId('field_id')->constrained('fields')->cascadeOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimiento_productos', function (Blueprint $table) {
            $table->dropColumn('lot_number');
            $table->dropColumn('expiration_date');
            $table->dropColumn('field_id');
            $table->dropColumn('wharehouse_id');
        });
    }
};
