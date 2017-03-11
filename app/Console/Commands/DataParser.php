<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GeoinfoVia;
use App\Models\IntendenciaData;
use App\Models\MujeresGeoJson;
use App\Models\TipoVia;
use App\Models\TituloVia;
use App\Models\ViasComplete;
use App\Models\ViasInfo;

class DataParser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parser:import {--data-type=} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse all files to construct the dataset.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $parse_type = $this->option('data-type');

        $this->info("Trying to import $parse_type");

        switch($parse_type) {
            case 'titulo':  // Nomenclator info
                $this->importTitulos();
                break;
            case 'tipo':    // Nomenclator info
                $this->importTipos();
                break;
            case 'viasIM':  // Nomenclator info
                $this->importStreetDataFromIM();
                break;
            case 'datauy':  // atunombre 2.0
                $this->importDataUY();
                break;
            case 'mujeres1.0':  // atunombre 1.0
                $this->importMujeres();
                break;
            case 'vias-geojson':
                $this->importViasGeoInfo(); // GeoJSON exported from QGIS after entering the shapefile from IM
                break;
            case 'vias-geo-shapefile':
                $this->importViasGeoInfo(false);    // Directly feeding the shapefile
            default:
                $this->error('Invalid type!');
                exit;
        }
    }


    /**
     * Import CSV from the IM with all the Titulos for vias into the DB.
     *
     * @see https://www.catalogodatos.gub.uy/dataset/vias-montevideo-con-cabezales-numeracion-significado-tipo-titulo
     * http://www.montevideo.gub.uy/sites/default/files/datos/nomenclator.zip ==> titulos_vias.csv
     */
    private function importTitulos()
    {
        $titulos_vias = $this->parseCsv();
        $headers = $titulos_vias['headers'];
        $data = collect($titulos_vias['data'])->each(function($titulo) use ($headers) {
            $values = collect($headers)->combine($titulo);
            TituloVia::create($values->toArray());
        });
        $this->info($data->count() .  " titulos have been imported.");
    }

    /**
     * Import CSV from the IM with all the Tipos for vias.
     *
     * @see http://www.montevideo.gub.uy/sites/default/files/datos/nomenclator.zip ==> tipos_vias.csv
     */
    private function importTipos()
    {
        $tipos_vias = $this->parseCsv();
        $headers = $tipos_vias['headers'];
        $data = collect($tipos_vias['data'])->each(function($titulo) use ($headers) {
            $values = collect($headers)->combine($titulo);
            TipoVia::create($values->toArray());
        });
        $this->info($data->count() .  " tipos have been imported.");
    }

    /**
     * Import CSV with all the IM with the street data into the DB.
     *
     * @see https://www.catalogodatos.gub.uy/dataset/vias-montevideo-con-cabezales-numeracion-significado-tipo-titulo and
     * http://www.montevideo.gub.uy/sites/default/files/datos/nomenclator.zip ==> vias.csv
     */
    private function importStreetDataFromIM()
    {
        $intendencia = $this->parseCsv();
        $headers = $intendencia['headers'];
        $bar = $this->output->createProgressBar(count($intendencia['data']));
        $data = collect($intendencia['data'])->each(function($titulo) use ($headers, $bar) {
            $bar->advance();
            // Ignore weird entries
            if (count($headers) !== count($titulo)) {
                return;
            }
            $values = collect($headers)->combine($titulo);
            IntendenciaData::create($values->toArray());
        });
        $bar->finish();
        $this->info("\n".$data->count() .  " streets have been imported.");
    }

    /**
     * Import data from latter classification of DATAUY containing the streets (ID),
     * their type, and a subtype (only for women). This data was classified on March, 2017.
     *
     *
     * @see https://catalogodatos.gub.uy/dataset/clasificacion-de-vias-de-montevideo-atunombre-uy/resource/9af580fc-a127-4e64-be50-5308d7ea25bb
     */
    private function importDataUY()
    {
        $columns = [
            'via'           => 0,
            'tipo'          => 1,
            'subtipo'       => 2,
        ];
        $classified_data = $this->parseCsv(',', $columns);
        $headers = $classified_data['headers'];
        $bar = $this->output->createProgressBar(count($classified_data['data']));
        $data = collect($classified_data['data'])->each(function($info) use ($headers, $bar) {
            $bar->advance();
            // Ignore malformed rows
            if (count($headers) !== count($info) || (empty($info[0]) && !is_numeric($info[0]))) {
                return;
            }
            // Build the model info
            $values = collect($headers)->combine($info);
            ViasInfo::create($values->toArray());
        });
        $bar->finish();
        $this->info("\n".$data->count() .  " rows have been imported.");
    }

    /**
     * Parse info from the GeoJSON from the first data classification made for
     * atunombre.uy 1.0. It contains all the data present on the first version
     * (first classification + extended bios).
     */
    private function importMujeres()
    {
        $filename = $this->argument('file');
        $geojson = json_decode(file_get_contents($filename), true);
        $i = 0;
        foreach ($geojson['features'] as $feature) {
            // clean up the data, remove unnecessary headers
            $headers_to_remove = [
                "nom_calle",
                "cod_depto",
                "cod_locali",
                "gid",
                "cab_desde_",
                "cab_hasta_",
                "cab_des_01",
                "cab_has_01",
                "sentido_nu",
                "cartodb_id",
                "created_at",
                "updated_at",
            ];
            foreach ($headers_to_remove as $header) {
                unset($feature['properties'][$header]);
            }

            // Remove negative numbers and comments on images
            foreach($feature['properties'] as &$property) {
                if ($property < 0 || $property == "Creo que esta la deberiamos eliminar") {
                    $property = null;
                }
            }

            // There are some repeated entries for the streets, only add them once to the DB.
            if (!MujeresGeoJson::where('cod_nombre', $feature['properties']['cod_nombre'])->exists()) {
                $i++;
                MujeresGeoJson::create($feature['properties']);
            }
        }
        $this->info("$i 'mujeres' have been imported.");
    }

    /**
     * Import the geographic data from MVD and store it into the DB
     *
     * @see https://www.catalogodatos.gub.uy/dataset/vias-de-transito-montevideo
     * @note: the geojson is a export of the link above to geojson changing the proyection
     *        to lat/long coordinates.
     */
    private function importViasGeoInfo($geojson = true)
    {
        if (!$geojson) {
            $this->error('not implemented yet!');
            exit;
        }
        // Read the geojson that comes from the shapefile (from the Intendencia)
        // and add all our extra data.
        $geojson = json_decode(file_get_contents($this->argument('file')), true);

        // Progress bar
        $bar = $this->output->createProgressBar(count($geojson['features']));

        foreach ($geojson['features'] as $feature ) {
            $bar->advance();

            // There is a weird record at the end of the geojson, ignore it!
            if (!isset($feature['properties'])) {
                continue;
            }

            $data = $feature['properties'];
            $data['geometry'] = isset($feature['geometry']) ? json_encode($feature['geometry']) : '';
            // There may be more than one entry per COD_NOMBRE (ID).
            GeoinfoVia::create($data);
        }
        $bar->finish();
        $this->info("\n".count($geojson['features']) .  " streets have been imported.");
    }

    /**
     * Parse a well-formed CSV and return an array with its data and headers.
     *
     * @param string        $separator Separator for the CSV file.
     * @param array|bool    $custom_columns [optional] If present, parse only the given columns.
     *
     * @return array
     */
    private function parseCsv($separator = ';', $custom_columns = false)
    {
        $filename = $this->argument('file');
        $data = [];
        if (($handle = fopen($filename, "r")) !== FALSE) {
            $headers = false;
            while (($data_line = fgetcsv($handle, 1000, $separator)) !== FALSE) {
                if (!$headers) {
                    if (!is_array($custom_columns)) {
                        foreach ($data_line as $value) {
                            $headers[] = trim(strtolower($value));
                        }
                    } else {
                        $headers = array_keys($custom_columns);
                    }
                    continue;
                }
                $temp = [];
                foreach ($data_line as $key => $value) {
                    // Clean up the values
                    $filtered = trim($value);
                    if (empty($filtered) && !is_numeric($filtered)) {  // Ignore empty values, but '0' is legit
                        $filtered = null;
                    }

                    if (!is_array($custom_columns)) {
                        $temp[] = $filtered;
                    } else {
                        if (in_array($key, array_values($custom_columns))) {
                            $temp[] = $filtered;
                        }
                    }
                }
                $data[]  = $temp;
            }
        }
        return ['headers' => $headers, 'data' => $data];
    }

}
