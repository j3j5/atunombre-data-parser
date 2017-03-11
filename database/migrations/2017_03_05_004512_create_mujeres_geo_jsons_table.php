<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMujeresGeoJsonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mujeres_geo_jsons', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->integer("cod_nombre")->unsigned();
            $table->string("nombre_de_calles_m_nombre_abreviado")->nullable();
            $table->string("nombre_de_calles_m_nombre")->nullable();
            $table->string("nombre_de_calles_m_nombre_de_clasificacion")->nullable();
            $table->string("nombre_de_calles_m_mujer___hombre___no_aplica")->nullable();
            $table->string("nombre_de_calles_m_nombre_mujer")->nullable();
            $table->string("nombre_de_calles_m_ano_inaugurada")->nullable();
            $table->integer("nombre_de_calles_m_ano_nac")->unsigned()->nullable();
            $table->integer("nombre_de_calles_m_ano_muerte")->unsigned()->nullable();
            $table->string("nombre_de_calles_m_bio_140")->nullable();
            $table->text("nombre_de_calles_m_bio_600")->nullable();
            $table->text("nombre_de_calles_m_significado_via")->nullable();
            $table->text("nombre_de_calles_m_bio_externa")->nullable();
            $table->string("nombre_de_calles_m_actividad_principal")->nullable();
            $table->string("nombre_de_calles_m_localidad")->nullable();
            $table->string("nombre_de_calles_m_departamento")->nullable();
            $table->integer("nombre_de_calles_m_uruaguaya")->unsigned()->nullable();
            $table->integer("nombre_de_calles_m_tvia_tipo_via")->unsigned()->nullable();
            $table->string("nombre_de_calles_m_etnia")->nullable();
            $table->string("nombre_de_calles_m_tit_titulo")->nullable();
            $table->integer("nombre_de_calles_m_comienzo_numeracion")->unsigned()->nullable();
            $table->integer("nombre_de_calles_m_fin_numeracion")->unsigned()->nullable();
            $table->text("nombre_de_calles_m_imagen")->nullable();
            $table->text("nombre_de_calles_m_obsevaciones")->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mujeres_geo_jsons');
    }
}
