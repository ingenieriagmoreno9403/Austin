<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvProductoCostoHistorial extends Model
{
    public $table = 'tbl_pv_productos_costo_historial';

    public $timestamps = false;

    protected $fillable = [
        'empresa',
        'producto_codigo',
        'producto_nombre',
        'precio_anterior',
        'precio_nuevo',
        'moneda_anterior',
        'moneda_nueva',
        'origen',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'precio_anterior' => 'float',
        'precio_nuevo' => 'float',
        'created_at' => 'datetime',
    ];
}
