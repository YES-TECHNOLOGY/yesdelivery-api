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
            $table->char('identification', 10)->unique()->comment("user identification");
            $table->char('ruc', 13)->unique()->nullable()->comment("user ruc");
            $table->string('name')->comment('name of the users');
            $table->string('lastname')->comment('surname of the users');
            $table->string('email')->unique()->comment("user email");
            $table->enum('gender',['male','female','other'])->nullable()->comment('gender of users');
            $table->string('password')->nullable()->comment('users password');
            $table->text('photography')->nullable()->comment('user photography');
            $table->string('driving_license_photography')->nullable()->comment('user driving license photography');
            $table->boolean('verified')->default(false)->comment('user verified');
            $table->dateTime('email_verified_at')->nullable()->comment('email date verify');
            $table->dateTime('remember_token_valid_time')->nullable()->comment('Remember token valid time');
            $table->boolean('active')->default(0)->comment('User is active');
            $table->string('google_id')->unique()->nullable()->comment('user google Id');
            $table->rememberToken();
            $table->timestamps();
            /*foreign keys*/
            $table->unsignedBigInteger('cod_rol')->comment('user role');
            $table->foreign('cod_rol')
                ->references('cod_rol')
                ->on('rols')->cascadeOnUpdate();
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
