<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturaBeca extends Model
{
    use HasFactory;

    protected $table = 'tblga_facturas_beca';

    protected $fillable = [
        'empresa_id',
        'mes',
        'anio',
        'folio',
        'facturama_id',
        'uuid',
        'cfdi_sign',
        'sat_cert_number',
        'sat_sign',
        'rfc_prov_certif',
        'original_string',
        'fecha_timbrado',
        'emisor_rfc',
        'emisor_nombre',
        'emisor_regimen_fiscal',
        'receptor_rfc',
        'receptor_nombre',
        'receptor_uso_cfdi',
        'receptor_regimen_fiscal',
        'receptor_codigo_postal',
        'subtotal',
        'iva',
        'total',
        'sin_iva',
        'forma_pago',
        'metodo_pago',
        'conceptos_json',
        'respuesta_api_json',
        'estado',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'sin_iva' => 'boolean',
        'fecha_timbrado' => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(GestionAlumnosEmpresa::class, 'empresa_id');
    }
}
