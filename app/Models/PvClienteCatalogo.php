<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvClienteCatalogo extends Model
{
    protected $table = 'tbl_pv_cliente_catalogo';

    protected $fillable = [
        'empresa',
        'codigo',
        'nombre',
        'anio',
        'origen',
        'synced_at',
    ];

    protected $casts = [
        'anio' => 'integer',
        'synced_at' => 'datetime',
    ];
}
