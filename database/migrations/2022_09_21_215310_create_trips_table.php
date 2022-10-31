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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['traveling','delivery', 'cancelled', 'completed']);
            $table->string('latitude_origin',50)->nullable()->comment('Latitude of origin trip');
            $table->string('longitude_origin',50)->nullable()->comment('Longitude of origin trip');
            $table->string('latitude_destination',50)->nullable()->comment('Latitude of destination trip');
            $table->string('longitude_destination',50)->nullable()->comment('Longitude of destination trip');
            $table->float('distance')->nullable()->comment('Distance of trip');
            $table->float('estimated_distance')->nullable()->comment('Estimated distance of trip');
            $table->float('estimated_duration')->nullable()->comment('Estimated duration of trip');
            $table->dateTime('start_time')->nullable()->comment('Start time of trip');
            $table->dateTime('end_time')->nullable()->comment('End time of trip');
            $table->float('waiting_time')->nullable()->comment('Waiting time of trip');
            $table->integer('qualification_driver')->nullable()->comment('Qualification of driver');
            $table->float('distance_price')->nullable()->comment('Price of distance');
            $table->float('time_price')->nullable()->comment('Price of time');
            $table->float('adicional_price')->nullable()->comment('Adicional price');
            $table->integer('qualification_client')->nullable()->comment('Qualification of client');
            $table->unsignedBigInteger('vehicle_id')->comment('Vehicle of trip');
            $table->unsignedBigInteger('conversation_id')->comment('Conversation in which the client requested the trip or request;');
            $table->timestamps();

            $table->foreign('vehicle_id')
                ->references('id')
                ->on('vehicles');
            $table->foreign('conversation_id')
                ->references('id')
                ->on('conversations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trips');
    }
};
