<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplementoPago extends Model
{
    protected $table = 'tblcomplementos_pago';

    protected $fillable = [
        'id_factura_original',
        'factura_uuid',
        'folio_factura',
        'folio_complemento',
        'facturama_id',
        'uuid',
        'fecha_pago',
        'forma_pago',
        'numero_parcialidad',
        'saldo_anterior',
        'monto_pagado',
        'saldo_insoluto',
        'receptor_rfc',
        'receptor_nombre',
        'estado',
        'respuesta_api',
        'created_by',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
    ];
}
