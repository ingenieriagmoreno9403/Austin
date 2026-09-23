<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvProductoCosto extends Model
{
    public $table = 'tbl_pv_productos_costo';

    protected $fillable = [
        'empresa',
        'producto_codigo',
        'producto_nombre',
        'costo_unitario',
        'moneda',
        'updated_by',
    ];

    protected $casts = [
        'costo_unitario' => 'float',
    ];
}
