<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class torres_taller extends Model
{
    use HasFactory;

    public $table = 'tbltorres_taller';

    protected $fillable = [
        'codigo_torre',
        'nombre_ubicacion',
        'estado',
        'created_by',
        'updated_by',
    ];
}

