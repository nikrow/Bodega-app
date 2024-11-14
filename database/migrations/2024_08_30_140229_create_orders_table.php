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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('orderNumber')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('field_id')->constrained('fields')->onDelete('cascade');
            $table->foreignId('crops_id')->constrained('fields')->onDelete('cascade');
            $table->SmallInteger('wetting');
            $table->json('equipment', ['turbonebulizador', 'turbocañon', 'helicoptero', 'dron', 'caracol', 'bomba_espalda', 'barra_levera_parada', 'azufrador']);
            $table->json('family', ['insecticida', 'herbicida', 'fertilizante', 'acaricida', 'fungicida', 'bioestimulante', 'regulador', 'bloqueador']);
            $table->json('epp', ['traje_aplicacion', 'guantes', 'botas', 'protector_auditivo', 'anteojos', 'antiparras', 'mascara_filtro']);
            $table->foreignId('updated_by')->constrained('users')->onDelete('cascade');
            $table->Json('applicators')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
