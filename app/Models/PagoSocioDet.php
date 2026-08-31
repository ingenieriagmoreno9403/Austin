<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoSocioDet extends Model
{
    protected $table = 'tblpagos_socios_det';

    protected $fillable = [
        'id_pago_enc',
        'num_plazo',
        'fecha_programada',
        'fecha_pago',
        'monto_programado',
        'monto_pagado',
        'status',
        'created_by',
        'updated_by',
    ];

    public function encabezado(): BelongsTo
    {
        return $this->belongsTo(PagoSocioEnc::class, 'id_pago_enc');
    }
}
