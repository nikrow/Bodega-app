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
        Schema::create('wharehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status');
            $table->ForeignId('created_by')->constrained('users');
            $table->ForeignId('field_id')->constrained('fields');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wharehouses');
    }
};
