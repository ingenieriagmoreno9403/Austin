<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class incidencias_servicio_catalogo extends Model
{
    use HasFactory;

    public $table = 'tblincidencias_servicio_catalogo';

    protected $fillable = [
        'nombre_incidencia',
        'estado',
        'created_by',
        'updated_by',
    ];
}

