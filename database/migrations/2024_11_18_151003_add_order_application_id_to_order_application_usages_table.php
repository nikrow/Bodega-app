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
        Schema::table('order_application_usages', function (Blueprint $table) {
            $table->foreignId('order_application_id')->nullable()->constrained('order_applications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_application_usages', function (Blueprint $table) {
            $table->dropForeign('order_application_id');
            $table->dropColumn('order_application_id');
        });
    }
};
