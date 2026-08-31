<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteNoExistencia extends Model
{
    protected $table = 'tbl_reporte_no_existencias';

    public $timestamps = true;

    const ESTATUS_PENDIENTE = 'PENDIENTE';
    const ESTATUS_EN_COMPRA = 'EN_COMPRA';
    const ESTATUS_ATENDIDO = 'ATENDIDO';
    const ESTATUS_CANCELADO = 'CANCELADO';

    const TIPO_PRODUCTO = 'PRODUCTO';
    const TIPO_MATERIA_PRIMA = 'MATERIA_PRIMA';

    protected $fillable = [
        'folio',
        'pedido_id',
        'pedido_detalle_id',
        'cotizacion_id',
        'cliente_id',
        'producto_id',
        'tipo',
        'descripcion',
        'cantidad_solicitada',
        'existencia_disponible',
        'cantidad_faltante',
        'estatus',
        'orden_compra_id',
        'observaciones',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'cantidad_solicitada' => 'decimal:2',
        'existencia_disponible' => 'decimal:2',
        'cantidad_faltante' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(VentaPedido::class, 'pedido_id');
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(VentaCotizacion::class, 'cotizacion_id');
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }
}
