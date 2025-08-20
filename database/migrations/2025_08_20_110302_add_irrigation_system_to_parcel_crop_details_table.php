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
            $table->string('irrigation_system')->nullable()->after('planting_scheme_id');
            $table->softDeletes();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parcel_crop_details', function (Blueprint $table) {
            $table->dropColumn('irrigation_system');
            $table->dropSoftDeletes();
        });
    }
};
