<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('returned_items', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id');
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->enum('return_type', ['stock', 'damaged']);
            $table->timestamp('returned_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('returned_items');
    }
};