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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->unsignedSmallInteger('price');
            $table->float('total_price')->virtualAs('quantity * price');
            $table->ForeignId('created_by')->constrained('users');
            $table->ForeignId('updated_by')->constrained('users');
            $table->ForeignId('field_id')->constrained('fields');
            $table->ForeignId('wharehouse_id')->constrained('wharehouses');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
