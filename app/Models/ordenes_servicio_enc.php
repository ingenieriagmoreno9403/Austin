<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ordenes_servicio_enc extends Model
{
    use HasFactory;

    public $table = 'tblordenes_servicio_enc';

    protected $fillable = [
        'no_orden',
        'tipo_vehiculo',
        'codigo_torre',
        'estado',
        'fecha_hora',
        'fecha_promesa',
        'created_by',
        'updated_by',
    ];
}

