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
        Schema::create('tractors', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->foreignId('field_id')->constrained('fields');
            $table->string('provider');
            $table->string('SapCode')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('hourometer', 10, 2)->nullable();
            $table->decimal('old_hourometer', 8, 2)->nullable();
            $table->date('last_hourometer_date')->nullable();
            $table->string('qrcode')->unique()->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tractors');
    }
};
