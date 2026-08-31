<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class servicios_taller_catalogo extends Model
{
    use HasFactory;

    public $table = 'tblservicios_taller_catalogo';

    protected $fillable = [
        'nombre_servicio',
        'estado',
        'created_by',
        'updated_by',
    ];
}

