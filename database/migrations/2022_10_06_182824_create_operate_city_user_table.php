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
        Schema::create('operate_city_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cod_user')->comment('user identifier');
            $table->unsignedBigInteger('cod_operate_city')->comment('operate city identifier');
            $table->date('start_date')->comment('start date');
            $table->date('end_date')->nullable()->comment('end date');
            $table->boolean('active')->default(true)->comment('active user in city');
            $table->text('comment')->nullable()->comment('comment for this user');
            $table->timestamps();

            $table->foreign('cod_user')
                ->references('id')
                ->on('users')->cascadeOnUpdate();

            $table->foreign('cod_operate_city')
                ->references('id')
                ->on('operate_cities')->cascadeOnUpdate();
            $table->unique(['cod_user','cod_operate_city']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('operate_city_users');
    }
};
