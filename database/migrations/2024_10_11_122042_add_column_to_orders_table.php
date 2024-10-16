<?php

use App\Models\Parcel;
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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('orderNumber');
            $table->unique(['orderNumber', 'field_id']);
            $table->foreignId('wharehouse_id')->constrained('wharehouses')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('wharehouse_id');

            $table->dropColumn('orderNumber');
        });
    }
};
