<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('field_id')->constrained('fields');
            $table->foreignId('machinery_id')->nullable()->constrained('machineries');
            $table->foreignId('tractor_id')->constrained('tractors');
            $table->foreignId('operator_id')->constrained('users');
            $table->foreignId('work_id')->constrained('works');
            $table->date('date');
            $table->decimal('initial_hourometer', 15, 2); // Horómetro inicial
            $table->decimal('hourometer', 15, 2);        // Horómetro final
            $table->decimal('hours', 10, 2);             // Horas trabajadas
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->text('observations')->nullable();
            $table->boolean('approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};