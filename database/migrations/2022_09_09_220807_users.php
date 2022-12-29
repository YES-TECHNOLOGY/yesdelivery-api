<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Users extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment("user identifier");
            $table->enum('type_identification',['cedula','visa','passport'])->comment('user identification type');
            $table->string('identification', 20)->unique()->comment("user identification");
            $table->string('name')->comment('name of the users');
            $table->string('lastname')->comment('surname of the users');
            $table->string('email')->unique()->comment("user email");
            $table->enum('gender',['male','female','other'])->nullable()->comment('gender of users');
            $table->char('cellphone','10')->nullable()->comment("user cellphone");
            $table->date('date_birth')->comment('user day of birth');
            $table->unsignedBigInteger('cod_nationality')->comment('identifier nationality of user');
            $table->unsignedBigInteger('cod_dpa')->comment('dpa identifier user live');
            $table->text('address')->comment('address where the user lives');
            $table->enum('size',['XS','S','M','L','XL','XXL','Other'])->comment('size user');
            $table->string('password')->nullable()->comment('users password');
            $table->string('photography')->nullable()->comment('user photography');
            $table->string('identification_front_photography')->nullable()->comment('front photo of user identification');
            $table->string('identification_back_photography')->nullable()->comment('photo of the back of user identification');
            $table->boolean('verified')->default(false)->comment('user verified');
            $table->dateTime('email_verified_at')->nullable()->comment('email date verify');
            $table->dateTime('remember_token_valid_time')->nullable()->comment('Remember token valid time');
            $table->boolean('active')->default(false)->comment('User is active');
            $table->string('google_id')->unique()->nullable()->comment('user google Id');
            $table->rememberToken();
            $table->timestamps();
            /*foreign keys*/
            $table->unsignedBigInteger('cod_rol')->comment('user role');
            $table->foreign('cod_rol')
                ->references('cod_rol')
                ->on('rols')->cascadeOnUpdate();

            $table->foreign('cod_dpa')
                ->references('cod_dpa')
                ->on('dpas')->cascadeOnUpdate();

            $table->foreign('cod_nationality')
                ->references('id')
                ->on('countries')->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
