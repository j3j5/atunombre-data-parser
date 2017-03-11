<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntendenciaDatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('intendencia_datas', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->integer('via')->unsigned();
            $table->string('nombre_abreviado')->nullable();
            $table->string('especificacion')->nullable();
            $table->string('nombre')->nullable();
            $table->string('nombre_de_clasificacion')->nullable();
            $table->text('significado_via')->nullable();
            $table->integer('tvia_tipo_via')->unsigned()->nullable();
            $table->integer('tit_titulo')->unsigned()->nullable();
            $table->integer('comienzo_numeracion')->unsigned()->nullable();
            $table->integer('fin_numeracion')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('intendencia_datas');
    }
}
