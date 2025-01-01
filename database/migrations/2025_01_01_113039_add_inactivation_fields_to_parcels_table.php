<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->unsignedBigInteger('deactivated_by')->nullable();
            $table->text('deactivation_reason')->nullable();

            $table->foreign('deactivated_by')->references('id')->on('users')->nullOnDelete();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->dropColumn('deactivated_at');
            $table->dropColumn('deactivated_by');
            $table->dropColumn('deactivation_reason');
            $table->dropForeign('parcels_deactivated_by_foreign');
        });
    }
};
