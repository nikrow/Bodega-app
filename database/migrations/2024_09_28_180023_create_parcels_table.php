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
        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->ForeignId('field_id')->constrained('fields');
            $table->ForeignId('crop_id')->constrained('crops');
            $table->smallInteger('planting_year');
            $table->string('planting-schema');
            $table->smallInteger('plants');
            $table->decimal('Surface_area', 7, 2);
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
        Schema::dropIfExists('parcels');
    }
};
