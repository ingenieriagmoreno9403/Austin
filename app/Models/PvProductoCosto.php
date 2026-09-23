<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvProductoCosto extends Model
{
    public $table = 'tbl_pv_productos_costo';

    protected $fillable = [
        'empresa',
        'card_code',
        'card_name',
        'producto_codigo',
        'producto_nombre',
        'mes',
        'costo_unitario',
        'moneda',
        'updated_by',
    ];

    protected $casts = [
        'costo_unitario' => 'float',
        'mes' => 'integer',
    ];
}
