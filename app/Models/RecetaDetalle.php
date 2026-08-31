<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecetaDetalle extends Model
{
    protected $table = 'tbl_recetas_detalle';

    public $timestamps = false;

    protected $fillable = [
        'receta_id',
        'producto_mp_id',
        'cantidad',
        'unidad_id',
        'porcentaje',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'porcentaje' => 'decimal:4',
    ];

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class, 'receta_id');
    }

    public function productoMp(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_mp_id');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_id');
    }
}
