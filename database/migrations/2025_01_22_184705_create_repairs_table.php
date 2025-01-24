<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->string('repair_name');
            $table->enum('device_category', ['iphone', 'android', 'other']);
            $table->decimal('cost', 10, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('repairs');
    }
};
