<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu', function (Blueprint $table) {
            $table->bigIncrements('cod_menu')->comment('menu identifier');
            $table->string('name')->comment('menu name');
            $table->bigInteger('order')->comment('menu order');
            $table->string('icon')->nullable()->comment('menu icon type awesome font. Ejem: home');
            $table->string('path')->nullable()->comment('route in the fronent');
            $table->unsignedBigInteger('cod_menu_parent')->nullable()->comment("In the case of being a submenu");
            $table->timestamps();
            $table->foreign('cod_menu_parent')
                ->references('cod_menu')
                ->on('menu')->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus');
    }
}
