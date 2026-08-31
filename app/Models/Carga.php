<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Carga extends Model
{
    protected $table = 'tbl_cargas';

    public const ESTATUS_PROGRAMADA = 'PROGRAMADA';
    public const ESTATUS_EN_RUTA = 'EN_RUTA';
    public const ESTATUS_DESPACHADA = 'DESPACHADA';

    public static array $estatusFlujo = [
        'PROGRAMADA' => 'Programada (avisada por Ventas)',
        'EN_RUTA' => 'Asignada a ruta',
        'DESPACHADA' => 'Despachada / salida',
    ];

    protected $fillable = [
        'folio',
        'pedido_id',
        'orden_produccion_id',
        'ruta_id',
        'orden_entrega',
        'orden_carga',
        'distancia_km',
        'destino_texto',
        'estatus',
        'fecha_programada',
        'hora_programada',
        'observaciones',
        'remision_firmada',
        'remision_archivo',
        'remision_firmada_at',
        'remision_firmada_por',
        'avisado_por',
        'avisado_at',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'avisado_at' => 'datetime',
        'distancia_km' => 'decimal:2',
        'remision_firmada' => 'boolean',
        'remision_firmada_at' => 'datetime',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(VentaPedido::class, 'pedido_id');
    }

    public function ordenProduccion(): BelongsTo
    {
        return $this->belongsTo(OrdenProduccion::class, 'orden_produccion_id');
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(RutaCarga::class, 'ruta_id');
    }

    public function avisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'avisado_por');
    }

    public function getEstatusTextoAttribute(): string
    {
        return self::$estatusFlujo[$this->estatus] ?? $this->estatus;
    }

    public static function destinoDesdeCliente(?Clientes $cliente): ?string
    {
        if (!$cliente) {
            return null;
        }

        $direccion = $cliente->direccionCompleta();
        if ($direccion !== '') {
            return $direccion;
        }

        return $cliente->nombre ?? null;
    }

    /** Metros a cargar: OP producida, o cantidad del pedido si no hay OP. */
    public function metrosCarga(): float
    {
        $op = $this->relationLoaded('ordenProduccion')
            ? $this->ordenProduccion
            : $this->ordenProduccion()->with('detalles')->first();

        if ($op) {
            return round((float) $op->total_metros_producidos, 2);
        }

        $pedido = $this->relationLoaded('pedido')
            ? $this->pedido
            : $this->pedido()->with('detalles')->first();

        if ($pedido && $pedido->relationLoaded('detalles')) {
            return round((float) $pedido->detalles->sum('cantidad'), 2);
        }

        if ($pedido) {
            return round((float) $pedido->detalles()->sum('cantidad'), 2);
        }

        return 0.0;
    }
}
