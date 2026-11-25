<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisicionDetalle extends Model
{
    protected $table = 'tbl_requisicion_detalles';

    protected $fillable = [
        'requisicion_id',
        'producto_id',
        'codigo',
        'descripcion',
        'unidad_medida',
        'cantidad',
        'cantidad_comprada',
        'especificaciones',
        'motivo',
        'fecha_requerida',
        'observaciones',
        'es_no_catalogado',
        'orden',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'cantidad_comprada' => 'float',
        'fecha_requerida' => 'date',
        'es_no_catalogado' => 'boolean',
        'orden' => 'integer',
    ];

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function cantidadPendiente(): float
    {
        return max(0, (float) $this->cantidad - (float) $this->cantidad_comprada);
    }
}
