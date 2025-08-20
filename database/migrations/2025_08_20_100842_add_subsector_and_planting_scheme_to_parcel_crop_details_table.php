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
        Schema::table('parcel_crop_details', function (Blueprint $table) {
            $table->string('subsector')->nullable()->after('id');
            $table->foreignId('planting_scheme_id')->nullable()->constrained('planting_schemes')->after('subsector');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parcel_crop_details', function (Blueprint $table) {
            $table->dropForeign(['planting_scheme_id']);
            $table->dropColumn('planting_scheme_id');
            $table->dropColumn('subsector');
        });
    }
};