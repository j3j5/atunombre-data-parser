Little Laravel app to import/export the data for [atunombre 2.0](http://atunombre.uy) project.

The repo comes with a preloaded SQLite DB which you can use out of the box, if you want to import this to your own DB, do as follow. Otherwise you can skip to the export step.

## Import
First, import the data. From the project root run:
```
$ php artisan parser:import --data-type=titulo resources/data/titulos_vias.csv
$ php artisan parser:import --data-type=tipo resources/data/tipos_vias.csv
$ php artisan parser:import --data-type=viasIM resources/data/vias.csv
$ php artisan parser:import --data-type=datauy resources/data/vias-clasificadas.csv
$ php artisan parser:import --data-type=mujeres resources/data/calles_mujeres.geojson
$ php artisan parser:import --data-type=vias-geojson resources/data/vias.geojson
```
Now you have all the info imported into your DB.

## Export
In order to export (`--filter` is optional):
```
$ php artisan parser:export --filter=Mujer mujeres.geojson
$ php artisan parser:export all-vias.geojson
```
The filters available are:
* Familia
* Astronómico
* Fauna
* Artes/Cultura
* Mujer
* Otro
* Geográfico
* Flora
* Pais/Nación/Región
* Histórico
* Lugar/Ciudad
* Descriptivo
* Hombre
