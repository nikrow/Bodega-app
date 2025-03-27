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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained('crops');
            $table->foreignId('field_id')->constrained('fields');
            $table->foreignId('machinery_id')->constrained('machineries');
            $table->foreignId('tractor_id')->constrained('tractors');   
            $table->foreignId('operator_id')->constrained('operators');
            $table->foreignId('work_id')->constrained('works');
            $table->date('date');
            $table->decimal('hourometer', 10, 2);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->string('observations')->nullable();
            $table->boolean('approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->dateTime('approved_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
