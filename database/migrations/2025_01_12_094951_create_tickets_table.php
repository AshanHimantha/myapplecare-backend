<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // Add this line
            $table->string('first_name');
            $table->string('last_name');
            $table->string('contact_number');
            $table->enum('priority', ['low', 'medium', 'high'])->default('low');
            $table->enum('device_category', ['iphone', 'android', 'other']);
            $table->string('device_model');
            $table->string('imei')->nullable();
            $table->text('issue');
            $table->decimal('service_charge', 10, 2)->nullable()->default('0');
            $table->enum('status', ['open', 'in_progress', 'completed'])->default('open');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tickets');
    }
};
