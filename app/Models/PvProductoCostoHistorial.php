<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvProductoCostoHistorial extends Model
{
    public $table = 'tbl_pv_productos_costo_historial';

    public $timestamps = false;

    protected $fillable = [
        'anio',
        'empresa',
        'card_code',
        'card_name',
        'producto_codigo',
        'producto_nombre',
        'mes',
        'precio_anterior',
        'precio_nuevo',
        'moneda_anterior',
        'moneda_nueva',
        'origen',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'anio' => 'integer',
        'precio_anterior' => 'float',
        'precio_nuevo' => 'float',
        'mes' => 'integer',
        'created_at' => 'datetime',
    ];
}
