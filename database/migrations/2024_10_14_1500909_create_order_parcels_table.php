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
        Schema::create('order_parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('fields')->cascadeOnDelete();
            $table->foreignId('parcel_id')->constrained('parcels')->cascadeOnDelete();
            $table->ForeignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->ForeignId('updated_by')->constrained('users')->cascadeOnDelete();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_parcels');
    }
};
