<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class facturacionproductos extends Model
{
    use HasFactory;

    public $table='tblfacturacionproductos';
    
    protected $fillable = [
        'facturama_id',
        'folio',
        'date',
        'reciver_rfc',
        'reciver_nombre',
        'subtotal',
        'total',
        'Uuid',
        'CfdiSign',
        'SatCertNumber',
        'SatSign',
        'RfcProvCertif',
        'OriginalString',
        'estado',
        'id_serv_enc',
        'tipo_serv',
        'cancelada',
        'dia_cancelacion',
        'observaciones_cancelacion',
        'referencia_factura',
        'metodo_pago',
        'forma_pago',
    ];
}
