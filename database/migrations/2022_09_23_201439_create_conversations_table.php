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
            $table->enum('status',['name','initializer','order','terminated','location','reference','assigned','delivery','assigning']);
            $table->enum('type_order',['delivery','taxi'])->nullable();
            $table->string('latitude',20)->nullable()->comment('latitude client');
            $table->string('longitude',20)->nullable()->comment('longitude client');
            $table->text('reference',20)->nullable()->comment('reference client');
            $table->boolean('deleted')->default(false);
            $table->unsignedBigInteger('recipient_phone_number')->comment('identifier wha phone number');
            $table->foreign('recipient_phone_number')
                ->references('id')
                ->on('whatsapp_numbers')->cascadeOnUpdate()->cascadeOnDelete();
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
