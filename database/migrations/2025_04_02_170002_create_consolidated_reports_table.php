<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consolidated_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tractor_id')->constrained('tractors');
            $table->foreignId('machinery_id')->nullable()->constrained('machineries');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('tractor_hours', 10, 2);
            $table->decimal('tractor_total', 15, 2);
            $table->decimal('machinery_hours', 10, 2);
            $table->decimal('machinery_total', 15, 2);
            $table->foreignId('created_by')->constrained('users');
            $table->dateTime('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consolidated_reports');
    }
};