<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReciboAguinaldo extends Model
{
    use HasFactory;
    public $table='recibos_aguinaldo';
    
    protected $fillable = [
        'id_facturafacil',
        'subtotal',
        'descuentos',
        'total',
        'observaciones',
        'nombre_receptor',
        'rfc_receptor',
        'descripcion_factura',
        'uuid_factura',
        'cfdisign',
        'satcertnumber',
        'satsign',
        'rfcprovcertif',
        'satus_factura',
        'original_string',
        'id_tblnominas_pagodet',
        'folio_int',
        'estado',
        'created_by'
    ];
}

