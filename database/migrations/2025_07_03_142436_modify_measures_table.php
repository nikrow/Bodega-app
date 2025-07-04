<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measures', function (Blueprint $table) {
            $table->index(['zone_id', 'sensor_type']);
        });
    }

    public function down(): void
    {
        Schema::table('measures', function (Blueprint $table) {
            $table->dropIndex(['zone_id', 'sensor_type']);
        });
    }
};