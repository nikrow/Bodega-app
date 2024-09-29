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
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->ForeignId('field_id')->constrained('fields');
            $table->foreignId('bodegaOrigen_id')->nullable()->constrained('wharehouses')->onDelete('set null');
            $table->foreignId('bodegaDestino_id')->nullable()->constrained('wharehouses')->onDelete('set null');
            $table->integer('cantidad');
            $table->string('tipo'); // entrada, salida, traslado
            $table->text('descripcion')->nullable();
            $table->ForeignId('created_by')->constrained('users');
            $table->ForeignId('updated_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
