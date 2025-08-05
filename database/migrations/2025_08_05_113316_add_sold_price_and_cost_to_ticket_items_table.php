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
        Schema::table('ticket_items', function (Blueprint $table) {
            $table->decimal('sold_price', 10, 2)->nullable()->after('serial');
            $table->decimal('cost', 10, 2)->nullable()->after('sold_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_items', function (Blueprint $table) {
            $table->dropColumn(['sold_price', 'cost']);
        });
    }
};
