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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('active_ingredients')->nullable();
            $table->enum('SAP_family', ['fertilizantes-enmiendas', 'fitosanitarios', 'fitoreguladores', 'bioestimulantes', 'otros']);
            $table->string('SAP_code')->unique();
            $table->enum('unit_measure', ['kilogramo', 'litro', 'unidad'])->nullable();
            $table->smallInteger('waiting_time')->nullable();
            $table->smallInteger('reentry')->nullable();
            $table->decimal('price', 13, 2);
            $table->enum('family', ['insecticida', 'herbicida', 'fertilizante', 'acaricida', 'fungicida', 'bioestimulante', 'regulador', 'bloqueador']);
            $table->ForeignId('created_by')->constrained('users')->nullable();
            $table->ForeignId('updated_by')->constrained('users')->nullable();
            $table->unsignedBigInteger('field_id')->nullable();
            $table->foreign('field_id')->references('id')->on('fields');
            $table->string('slug')->unique();
            $table->decimal('dosis_min', 8, 3)->nullable();
            $table->decimal('dosis_max', 8, 3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
