<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConceptosProyectos extends Model
{
    use HasFactory;
    
    protected $table = 'tconceptos_proyectos';
    
    protected $fillable = [
        'id_proyecto',
        'nombre_concepto',
        'descripcion_concepto',
        'costo_concepto',
        'otrosconceptos1',
        'otrosconceptos2',
        'otrsoconceptos3',
        'created_at',
        'updated_at'
    ];
    
    public $timestamps = true;
}
