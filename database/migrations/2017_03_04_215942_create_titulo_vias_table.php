<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTituloViasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('titulo_vias', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->integer('titulo')->unsigned();  // ID
            $table->string('descripcion')->nullable();
            $table->string('desc_abreviada')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('titulo_vias');
    }
}
