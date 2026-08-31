<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturaBecaError extends Model
{
    use HasFactory;

    protected $table = 'tblga_facturas_beca_errores';

    protected $fillable = [
        'empresa_id',
        'mes',
        'anio',
        'folio',
        'emisor_rfc',
        'emisor_nombre',
        'receptor_rfc',
        'receptor_nombre',
        'subtotal',
        'total',
        'http_status',
        'error_message',
        'error_details',
        'request_payload',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];
}
