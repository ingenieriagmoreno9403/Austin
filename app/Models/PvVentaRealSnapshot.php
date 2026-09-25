<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvVentaRealSnapshot extends Model
{
    public $table = 'tbl_pv_venta_real_snapshot';

    protected $fillable = [
        'empresa',
        'cliente_codigo',
        'anio',
        'por_cuenta',
        'origen',
        'synced_at',
        'synced_by',
    ];

    protected $casts = [
        'anio' => 'integer',
        'por_cuenta' => 'array',
        'synced_at' => 'datetime',
    ];
}
