<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Especialidad extends Model
{
    use HasFactory;

    protected $table = 'tblespecialidades';

    protected $fillable = [
        'numero_interno_especialidad',
        'nombre_especialidad',
        'descripcion',
        'otros_conceptos1',
        'otros_conceptos2',
        'otros_conceptos34',
        'activo',
    ];

    protected $casts = [
        'numero_interno_especialidad' => 'integer',
        'otros_conceptos34' => 'decimal:2',
        'activo' => 'boolean',
    ];
}
