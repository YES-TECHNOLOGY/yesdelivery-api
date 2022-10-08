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
        Schema::create('operate_cities', function (Blueprint $table) {
            $table->id();
            $table->string('type','100')->comment('type of plan, for example delivery, taxi, if premium.');
            $table->float('minimum_price')->comment('minimum price for the plan.');
            $table->float('night_km_price')->comment('price per km for night');
            $table->float('day_km_price')->comment('price per km for day');
            $table->float('night_min_price')->comment('price per min for night');
            $table->float('day_min_price')->comment('price per min for day');
            $table->float('additional_price')->default(0.00)->comment('price for additional');
            $table->time('night_start_time')->comment('start time for night');
            $table->time('night_end_time')->comment('end time for night');
            $table->boolean('active')->default(true)->comment('if the operate city is active');
            $table->text('comment')->nullable()->comment('comment for the operate city');
            $table->unsignedBigInteger('cod_dpa')->comment('dpa identifier');
            $table->foreign('cod_dpa')
                ->references('cod_dpa')
                ->on('dpas')->cascadeOnUpdate();
            $table->timestamps();
            $table->unique(['type','cod_dpa']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('operate_cities');
    }
};
