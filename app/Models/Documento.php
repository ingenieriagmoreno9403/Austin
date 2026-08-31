<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'tbldocumentos';

    protected $fillable = [
        'nombre_documento',
        'descripcion_documento',
        'otros_conceptos1',
        'otros_conceptos2',
        'otros_conceptos34',
        'activo',
    ];

    protected $casts = [
        'otros_conceptos34' => 'decimal:2',
        'activo' => 'boolean',
    ];
}
