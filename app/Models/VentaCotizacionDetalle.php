<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaCotizacionDetalle extends Model
{
    public $table = 'tbl_cotizaciones_detalle';

    public $timestamps = false;

    protected $fillable = [
        'cotizacion_id',
        'producto_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'precio_unitario_con_iva',
        'descuento',
        'importe',
        'importe_con_iva',
        'created_at',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'precio_unitario_con_iva' => 'decimal:2',
        'descuento' => 'decimal:2',
        'importe' => 'decimal:2',
        'importe_con_iva' => 'decimal:2',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(VentaCotizacion::class, 'cotizacion_id');
    }
}
