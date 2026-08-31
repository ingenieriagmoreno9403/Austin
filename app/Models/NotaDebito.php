<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaDebito extends Model
{
    protected $table = 'tblnotas_debito';

    protected $fillable = [
        'id_factura_original',
        'factura_uuid',
        'folio_factura',
        'tipo_documento',
        'folio_nota',
        'facturama_id',
        'uuid',
        'motivo',
        'motivo_descripcion',
        'tipo_relacion',
        'subtotal',
        'iva',
        'total',
        'receptor_rfc',
        'receptor_nombre',
        'estado',
        'respuesta_api',
        'created_by',
    ];
}
