<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GeoinfoVia;
use App\Models\IntendenciaData;
use App\Models\MujeresGeoJson;
use App\Models\ViasInfo;

class DataExporter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parser:export
                            {--filter= : If present, it\'ll add a filter by type}
                            {output_file : The file where the info will be exported.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the data to a GeoJSON.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->export($this->option('filter'));
    }

    /**
     * Export a merge of all stored data with the given GeoJSON
     * and export it as GeoJSON.
     *
     * @param string $filter [Optional] Filter the types 'typo' by a given string, eg. "Mujer".
     *
     */
    private function export(string $filter = null)
    {
        $geojson = [
            "type" => "FeatureCollection",
            "crs" => [
                "type" => "name",
                "properties" =>  [
                    "name" => "urn:ogc:def:crs:OGC:1.3:CRS84",
                ],
            ],
        ];


        $query = ViasInfo::getQuery();
        if ($filter) {
            $query->where('tipo', $filter);
        }
        $vias_info = $query->get();
        // Progress bar
        $bar = $this->output->createProgressBar($vias_info->count());

        $new_features = [];
        foreach ($vias_info as $via_info) {
            $bar->advance();

            // Find the street on the data from the IM (nomenclator)
            $intendencia = IntendenciaData::where('via', $via_info->via)->first();
            // Try to find if the street was present on the GeoJSON from atunombre 1.0
            $mujer = MujeresGeoJson::where('cod_nombre', $via_info->via)->first();
            // Find all geopoints for this street
            $geoinfo = GeoinfoVia::where('COD_NOMBRE', $via_info->via)->get();

            $feature = [];
            foreach ($geoinfo as $geo) {
                $geo_data = $geo->toArray();
                $remove = ['id', 'created_at', 'updated_at', 'geometry'];
                foreach($remove as $key) {
                    unset($geo_data[$key]);
                }
                $feature['type'] = "Feature";
                $feature['properties'] = $geo_data;
                $feature['geometry'] = json_decode($geo->geometry, true);
                // Overwrite the name to fix the weird encoding!!!
                $feature['properties']['NOM_CALLE'] = $intendencia->nombre;

                $data_to_add = [
                    // Data from meetup (atunombre 2.0)
                    "extra_nombre_tipo"       => $via_info->tipo,
                    "extra_nombre_subtipo"    => $via_info->subtipo,

                    // Data from Intendencia
                    "extra_nombre_abreviado"              => $intendencia->nombre_abreviado,
                    "extra_nombre"                        => $intendencia->nombre,
                    "extra_nombre_de_clasificacion"       => $intendencia->nombre_de_clasificacion,
                    "extra_especificacion"                => $intendencia->especificacion,
                    "extra_significado_via"               => $intendencia->significado_via,
                    "extra_comienzo_numeracion"           => $intendencia->comienzo_numeracion,
                    "extra_fin_numeracion"                => $intendencia->fin_numeracion,
                    "extra_tipo_via_descripcion"                    => $intendencia->tipo ? $intendencia->tipo->descripcion : null,
                    "extra_tipo_via_descripcion_abreviada"          => $intendencia->tipo ? $intendencia->tipo->desc_abreviada: null,
                    "extra_via_nombre_titulo_descripcion"           => $intendencia->titulo ? $intendencia->titulo->descripcion : null,
                    "extra_via_nombre_titulo_descripcion_abreviada" => $intendencia->titulo ? $intendencia->titulo->desc_abreviada: null,

                    // Data from the previous GeoJSON (atunombre 1.0)
                    "extra_ano_inaugurada"                => $mujer->nombre_de_calles_m_ano_inaugurada ?? null,
                    "extra_ano_nac"                       => $mujer->nombre_de_calles_m_ano_nac ?? null,
                    "extra_ano_muerte"                    => $mujer->nombre_de_calles_m_ano_muerte ?? null,
                    "extra_bio_140"                       => $mujer->nombre_de_calles_m_bio_140 ?? null,
                    "extra_bio_600"                       => $mujer->nombre_de_calles_m_bio_600 ?? null,
                    "extra_bio_externa"                   => $mujer->nombre_de_calles_m_bio_externa ?? null,
                    "extra_actividad_principal"           => $mujer->nombre_de_calles_m_actividad_principal ?? null,
                    "extra_localidad"                     => $mujer->nombre_de_calles_m_localidad ?? null,
                    "extra_departamento"                  => $mujer->nombre_de_calles_m_departamento ?? null,
                    "extra_uruaguaya"                     => $mujer->nombre_de_calles_m_uruaguaya ?? null,
                    "extra_etnia"                         => $mujer->nombre_de_calles_m_etnia ?? null,
                    "extra_imagen"                        => $mujer->nombre_de_calles_m_imagen ?? null,
                    "extra_obsevaciones"                  => $mujer->nombre_de_calles_m_obsevaciones ?? null,
                ];

                $feature['properties'] = array_merge($feature['properties'], $data_to_add);
                $new_features[] = $feature;
            }
        }

        $geojson['features'] = $new_features;

        $export_filename = $this->argument('output_file');

        file_put_contents($export_filename, json_encode($geojson));
        $bar->finish();

        $this->info("\nDone. You can see your file at ".getcwd().DIRECTORY_SEPARATOR.$export_filename);
    }
}
