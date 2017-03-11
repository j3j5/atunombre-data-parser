<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTipoViasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tipo_vias', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->integer('tipo_via')->unsigned();
            $table->string('descripcion');
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
        Schema::dropIfExists('tipo_vias');
    }
}
