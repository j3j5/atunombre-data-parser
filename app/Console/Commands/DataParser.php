<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IntendenciaData;
use App\Models\TipoVia;
use App\Models\MujeresGeoJson;
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

        switch($parse_type) {
            case 'titulo':
                $this->importTitulos();
                break;
            case 'tipo':
                $this->importTipos();
                break;
            case 'intendencia':
                $this->importStreetDataFromIM();
                break;
            case 'datauy':
                $this->importDataUY();
                break;
            case 'mujeres':
                $this->importMujeres();
                break;
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
        collect($titulos_vias['data'])->each(function($titulo) use ($headers) {
            $values = collect($headers)->combine($titulo);
            TituloVia::create($values->toArray());
        });
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
        collect($tipos_vias['data'])->each(function($titulo) use ($headers) {
            $values = collect($headers)->combine($titulo);
            TipoVia::create($values->toArray());
        });
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
        collect($intendencia['data'])->each(function($titulo) use ($headers) {
            if (count($headers) !== count($titulo)) {
                dump($headers, $titulo);
                return;
            }
            $values = collect($headers)->combine($titulo);
            IntendenciaData::create($values->toArray());
        });
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
        $data = $this->parseCsv(';', $columns);
        $headers = $data['headers'];
        collect($data['data'])->each(function($info) use ($headers) {

            // Ignore malformed rows
            if (count($headers) !== count($info) || empty($info[0])) {
                return;
            }
            // Build the model info
            $values = collect($headers)->combine($info);
            ViasInfo::create($values->toArray());
        });
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
                MujeresGeoJson::create($feature['properties']);
            }
        }
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
                    if (empty($filtered)) {
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
