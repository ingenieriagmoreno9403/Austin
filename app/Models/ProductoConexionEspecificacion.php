<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoConexionEspecificacion extends Model
{
    protected $table = 'tbl_producto_conexion_especificaciones';

    protected $fillable = [
        'producto_id',
        'codigo',
        'tipo',
        'diametro_mm',
        'diametro_nominal',
        'rd',
        'peso_tubo_kg_m',
        'peso_kg_pieza',
        'material',
        'estatus',
    ];

    protected $casts = [
        'diametro_mm' => 'decimal:3',
        'rd' => 'decimal:3',
        'peso_tubo_kg_m' => 'decimal:4',
        'peso_kg_pieza' => 'decimal:4',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }

    public function scopeActivas($query)
    {
        return $query->where('estatus', 'ACTIVO');
    }
}
