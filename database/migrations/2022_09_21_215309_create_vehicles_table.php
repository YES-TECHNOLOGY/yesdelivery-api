<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id()->comment('vehicle identifier');
            $table->char('registration_number',8)->unique()->comment('vehicle registration number');
            $table->string('brand',50)->comment('vehicle brand');
            $table->string('model',50)->comment('vehicle model');
            $table->year('year_manufacture')->comment('year of manufacture of the vehicle');
            $table->string('color',50)->comment('vehicle color');
            $table->string('type',50)->comment('vehicle type ej: Moto');
            $table->string('registration_photography')->nullable()->comment('vehicle registration photo');
            $table->string('active')->default(0)->comment('vehicle active');
            $table->enum('status',['available','not_available','disconnected'])->default('not_available')->comment('vehicle status');

            $table->unsignedBigInteger('cod_user')->nullable()->comment('user identifier');
            $table->foreign('cod_user')
                ->references('id')
                ->on('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
};
