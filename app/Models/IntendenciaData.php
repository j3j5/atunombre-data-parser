<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntendenciaData extends Model
{
    protected $guarded = [];

    public function tipo()
    {
        return $this->hasOne(TipoVia::class, 'id', 'tvia_tipo_via');
    }

    public function titulo()
    {
        return $this->hasOne(TituloVia::class, 'id', 'tit_titulo');
    }
}
