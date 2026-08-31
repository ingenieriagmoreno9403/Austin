<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoTuboEspecificacion extends Model
{
    protected $table = 'tbl_producto_tubo_especificaciones';

    protected $fillable = [
        'producto_id',
        'material',
        'diametro_nominal',
        'diametro_exterior_pulg',
        'psi',
        'rd',
        'espesor_pulg',
        'peso_kg_m',
        'estatus',
    ];

    protected $casts = [
        'diametro_exterior_pulg' => 'decimal:6',
        'psi' => 'decimal:2',
        'rd' => 'decimal:2',
        'espesor_pulg' => 'decimal:6',
        'peso_kg_m' => 'decimal:4',
    ];

    public function producto()
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }
}
