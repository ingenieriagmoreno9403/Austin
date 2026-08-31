<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GestionAlumnosEmpresa extends Model
{
    protected $table = 'tblga_empresas';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefonos',
        'correos',
        'fecha_fin',
        'giro_empresarial',
        'estado',
        'municipio',
        'razon_social',
        'rfc',
        'regimen_fiscal',
        'regimen_fiscal_descripcion',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'codigo_postal',
        'uso_cfdi',
        'correo_fiscal',
    ];

    protected $casts = [
        'fecha_fin' => 'date',
    ];
}
