<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('work_logs', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('fields')->cascadeOnDelete();
            $table->foreignId('parcel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('responsible_id')->constrained('users'); 
            $table->foreignId('contractor_id')->nullable()->constrained('contractors');
            $table->foreignId('task_id')->constrained('tasks');
            $table->unsignedInteger('people_count')->nullable();        
            $table->decimal('quantity', 12, 3)->nullable();         
            $table->string('unit_type')->nullable();                 
            $table->boolean('by_jornada')->default(false);               
            $table->boolean('by_unit')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('work_logs');
    }
};
