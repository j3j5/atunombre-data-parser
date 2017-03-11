<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateViasCompletesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vias_completes', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->integer('via')->unsigned();     // ID

            // DATA UY
            $table->string('tipo');
            $table->string('subtipo');
            $table->text('extended_bio');

            // DATA FROM Intendencia
            $table->string('nombre_abreviado');
            $table->string('especificacion');
            $table->string('nombre');
            $table->string('nombre_de_clasificacion');
            $table->text('significado_via');
            $table->integer('tvia_tipo_via')->unsigned();
            $table->integer('tit_titulo')->unsigned();
            $table->integer('comienzo_numeracion')->unsigned();
            $table->integer('fin_numeracion')->unsigned();


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vias_completes');
    }
}
