<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\StatusType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('number');
            $table->foreignId('provider_id')->constrained();
            $table->date('date');
            $table->string('status')->default(StatusType::PENDIENTE->value);
            $table->boolean('is_received')->default(false);
            $table->string('observation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
