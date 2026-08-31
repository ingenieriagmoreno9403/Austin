<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatosFiscalesEmpresa extends Model
{
    protected $table = 'tbl_datos_fiscales_empresa';

    protected $fillable = [
        'razon_social',
        'rfc',
        'regimen_fiscal',
        'regimen_fiscal_descripcion',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'municipio',
        'estado',
        'codigo_postal',
        'pais',
        'telefono',
        'correo',
        'logo',
        'estatus',
        'usr_facturama',
        'pwd_facturama',
    ];

    protected $hidden = [
        'pwd_facturama',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
