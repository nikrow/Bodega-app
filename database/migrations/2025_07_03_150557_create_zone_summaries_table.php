<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zone_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained()->onDelete('cascade');
            $table->foreignId('zone_id')->constrained()->onDelete('cascade');
            $table->decimal('current_temperature', 8, 2)->nullable();
            $table->timestamp('current_temperature_time')->nullable();
            $table->decimal('min_temperature_daily', 8, 2)->nullable();
            $table->timestamp('min_temperature_time')->nullable();
            $table->decimal('max_temperature_daily', 8, 2)->nullable();
            $table->timestamp('max_temperature_time')->nullable();
            $table->decimal('daily_rain', 8, 2)->nullable();
            $table->timestamp('daily_rain_time')->nullable();
            $table->decimal('current_humidity', 8, 2)->nullable();
            $table->timestamp('current_humidity_time')->nullable();
            $table->decimal('chill_hours_accumulated', 8, 2)->nullable();
            $table->timestamp('chill_hours_accumulated_time')->nullable();
            $table->decimal('chill_hours_daily', 8, 2)->nullable();
            $table->timestamp('chill_hours_daily_time')->nullable();
            $table->timestamps();
            
            $table->index('zone_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_summaries');
    }
};