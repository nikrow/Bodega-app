<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFertilizerMappingsTable extends Migration
{
    public function up()
    {
        Schema::create('fertilizer_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('fertilizer_name')->nullable();
            $table->string('excel_column_name')->unique();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('dilution_factor', 5, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fertilizer_mappings');
    }
}