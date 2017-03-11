<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeoinfoviasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('geoinfo_vias', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->string("NOM_CALLE")->nullable();
            $table->integer("COD_DEPTO")->unsigned()->nullable();
            $table->integer("COD_LOCALI")->unsigned()->nullable();
            $table->float("GID")->nullable();
            $table->integer("CAB_DESDE_")->unsigned()->nullable();
            $table->integer("CAB_HASTA_")->unsigned()->nullable();
            $table->integer("CAB_DES_01")->unsigned()->nullable();
            $table->integer("CAB_HAS_01")->unsigned()->nullable();
            $table->integer("SENTIDO_NU")->unsigned()->nullable();
            $table->integer("COD_NOMBRE")->unsigned()->nullable();

            $table->json("geometry"); // JSON containing the geometry info

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('geoinfo_vias');
    }
}
