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
        Schema::table('order_applications', function (Blueprint $table) {
            $table->decimal('parcel_surface_snapshot', 10, 2)
                ->nullable()
                ->after('surface')
                ->comment('Snapshot of the parcel surface at the time of application');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_applications', function (Blueprint $table) {
            $table->dropColumn('parcel_surface_snapshot');
        });
    }
};
