<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDpasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dpas', function (Blueprint $table) {
            $table->bigIncrements('cod_dpa')->comment('dpa identify internal');
            $table->string('identify','20')->comment('dpa identify external');
            $table->text('name')->comment('dpa name');
            $table->enum('type',['province','canton','parish'])->comment('dpa parent');
            $table->bigInteger('dpa_parent')->nullable()->comment('dpa parent');
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
        Schema::dropIfExists('dpas');
    }
}
