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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id()->comment('identifier conversation');
            $table->char('display_phone_number','12');
            $table->char('phone_number_id','50');
            $table->enum('status',['name','initializer','order','terminated','location','reference','assigned','assigning']);
            $table->enum('status_user',['accept', 'reject', 'cancel', 'pending','none'])->default('none')->comment('status of user');
            $table->enum('type_order',['delivery','taxi'])->nullable()->comment('type of order');
            $table->string('latitude',20)->nullable()->comment('latitude client');
            $table->string('longitude',20)->nullable()->comment('longitude client');
            $table->text('reference',20)->nullable()->comment('reference client');
            $table->boolean('deleted')->default(false);
            $table->unsignedBigInteger('recipient_phone_number')->comment('identifier wha phone number');
            $table->unsignedBigInteger('operate_city_id')->nullable()->comment('identifier operate city');

            $table->foreign('operate_city_id')
                ->references('id')
                ->on('operate_cities')->cascadeOnUpdate();

            $table->foreign('recipient_phone_number')
                ->references('id')
                ->on('whatsapp_numbers')->cascadeOnUpdate()->cascadeOnDelete();

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
        Schema::dropIfExists('conversations');
    }
};
