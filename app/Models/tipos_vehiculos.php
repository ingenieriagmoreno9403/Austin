<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tipos_vehiculos extends Model
{
    use HasFactory;

    public $table = 'tbltipos_vehiculos';

    protected $fillable = [
        'tipo_vehiculo',
        'estado',
        'created_by',
        'updated_by',
    ];
}

