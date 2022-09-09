<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->bigIncrements('cod_log')->comment('logs identifier');
            $table->enum('type',['alert','info','critical'])->comment('error type');
            $table->string('ip')->nullable()->comment('ip conection');
            $table->string('user_agent')->nullable()->comment('User Agent conection');
            $table->text('log')->comment('log detail');
            $table->text('origin')->nullable()->comment('log origin');
            $table->unsignedBigInteger('id_user')->nullable()->comment('user identifier');
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
        Schema::dropIfExists('logs');
    }
}
