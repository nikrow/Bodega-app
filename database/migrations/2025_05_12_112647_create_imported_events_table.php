<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportedEventsTable extends Migration
{
    public function up()
    {
        Schema::create('imported_events', function (Blueprint $table) {
            $table->id();
            $table->string('tenant')->nullable();
            $table->foreignId('batch_id')->nullable()->constrained('import_batches')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->dateTime('date_time')->nullable();
            $table->integer('duration')->nullable();
            $table->decimal('quantity_m3', 10, 2)->nullable();
            $table->json('fertilizers')->nullable(); 
            $table->string('status')->default('pending'); 
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('imported_events');
    }
}