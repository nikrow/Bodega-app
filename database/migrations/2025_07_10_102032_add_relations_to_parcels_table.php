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
        Schema::table('parcels', function (Blueprint $table) {
            $table->foreignId('planting_scheme_id')
                ->nullable()
                ->constrained('planting_schemes') 
                ->onDelete('set null')
                ->after('crop_id');
            $table->string('sdp')->nullable()->after('name'); // Adding 'sdp' column after 'name'
            $table->string('irrigation_system')->nullable()->after('sdp'); // Adding 'irrigation_system' column after 'sdp'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropColumn(['sdp', 'irrigation_system', 'planting_scheme']); 
        });
    }
};
