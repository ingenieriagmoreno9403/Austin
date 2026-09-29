<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvProductoClienteCatalogo extends Model
{
    protected $table = 'tbl_pv_producto_cliente_catalogo';

    protected $fillable = [
        'empresa',
        'cliente_codigo',
        'codigo',
        'nombre',
        'grupo',
        'costo',
        'anio',
        'origen',
        'synced_at',
    ];

    protected $casts = [
        'costo' => 'float',
        'anio' => 'integer',
        'synced_at' => 'datetime',
    ];
}
