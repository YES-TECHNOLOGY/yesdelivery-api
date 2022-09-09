<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('access', function (Blueprint $table) {
            $table->bigIncrements('cod_access')->comment('access identifier');
            $table->string('name')->comment('access name');
            $table->string('endpoint')->comment('endpoint of the api you will access');
            $table->enum('method',['GET','POST','PUT','DELETE','OPTIONS'])->comment('Action you could take at the endpoint');
            $table->text('detail')->nullable();
            $table->unsignedBigInteger('cod_menu')->comment('Foreign key pointing to front uri');
            $table->timestamps();
            $table->foreign('cod_menu')
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
        Schema::dropIfExists('accesses');
    }
}
