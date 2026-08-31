<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivoCancelacionProduccion extends Model
{
    protected $table = 'tbl_motivos_cancelacion_produccion';

    protected $fillable = [
        'codigo',
        'nombre',
        'categoria',
        'requiere_nota',
        'activo',
        'orden',
    ];

    protected $casts = [
        'requiere_nota' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public static array $categorias = [
        'COMERCIAL' => 'Comercial / pedido',
        'PLANEACION' => 'Planeación',
        'MATERIALES' => 'Materiales',
        'TECNICA' => 'Técnica',
        'CALIDAD' => 'Calidad',
        'OTRO' => 'Otro',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }
}
