<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ticket_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('part_id')->nullable()->constrained();
            $table->foreignId('repair_id')->nullable()->constrained();
            $table->integer('quantity')->nullable();
            $table->enum('type', ['part','repair']);
            $table->text('serial')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_items');
    }
};
