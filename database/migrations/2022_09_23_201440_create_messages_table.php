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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->text('whatsapp_id')->nullable()->comment('identifier whatsapp message');
            $table->text('message');
            $table->string('type','100');
            $table->string('mime_type','100')->nullable();
            $table->text('url')->nullable();
            $table->enum('status',['sent','delivered','read','failed','deleted'])->nullable();
            $table->timestamp('timestamp_read')->nullable();
            $table->timestamp('timestamp_delivered')->nullable();
            $table->boolean('send_user');
            $table->unsignedBigInteger('conversation_id')->comment('identifier conversation');
            $table->timestamps();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('conversations')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
};
