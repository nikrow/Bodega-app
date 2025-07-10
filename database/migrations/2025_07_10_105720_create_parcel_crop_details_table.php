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
        Schema::create('parcel_crop_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_id')
                ->constrained('parcels')
                ->onDelete('cascade');
            $table->foreignId('crop_id')
                ->constrained('crops')
                ->onDelete('cascade');
            $table->foreignId('variety_id')
                ->nullable()
                ->constrained('varieties')
                ->onDelete('set null');
            $table->foreignId('rootstock_id')
                ->nullable()
                ->constrained('rootstocks')
                ->onDelete('set null');
            $table->decimal('surface',8,2)->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['parcel_id', 'crop_id']);
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcel_crop_details');
    }
};
