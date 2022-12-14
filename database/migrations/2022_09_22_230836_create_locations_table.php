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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('latitude',20)->comment('latitude vehicle');
            $table->string('longitude',20)->comment('longitude vehicle');
            $table->enum('status',['connected','traveling','disconnected'])->comment('status vehicle');
            $table->unsignedBigInteger('cod_trip')->nullable()->comment('trip id');
            $table->unsignedBigInteger('cod_vehicle')->comment('vehicle identifier');
            $table->foreign('cod_vehicle')
                ->references('id')
                ->on('vehicles')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('cod_trip')
                ->references('id')
                ->on('trips')->cascadeOnUpdate()->restrictOnDelete();
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
        Schema::dropIfExists('locations');
    }
};
