<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Escuela extends Model
{
    use HasFactory;

    protected $table = 'tblescuelas';

    /**
     * Columnas reales en BD: id, nombre, Direccion, telefono, numero_sep,
     * otros_conceptos1, otros_conceptos2, otros_conceptos34, activo (si existe), timestamps.
     */
    protected $fillable = [
        'nombre',
        'Direccion',
        'telefono',
        'numero_sep',
        'otros_conceptos1',
        'otros_conceptos2',
        'otros_conceptos34',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
