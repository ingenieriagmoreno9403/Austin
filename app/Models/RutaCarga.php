<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RutaCarga extends Model
{
    protected $table = 'tbl_rutas_carga';

    public const ESTATUS_ARMADA = 'ARMADA';
    public const ESTATUS_MATERIAL_UBICADO = 'MATERIAL_UBICADO';
    public const ESTATUS_CARGANDO = 'CARGANDO';
    public const ESTATUS_CHECKLIST = 'CHECKLIST_OK';
    public const ESTATUS_EVIDENCIA = 'EVIDENCIA';
    public const ESTATUS_REMISION = 'REMISION_FIRMADA';
    public const ESTATUS_CERRADA = 'CERRADA';

    public const CHOFER_INTERNO = 'INTERNO';
    public const CHOFER_EXTERNO = 'EXTERNO';

    /** Secuencia operativa (después de armar la ruta). */
    public static array $secuenciaOperativa = [
        self::ESTATUS_ARMADA,
        self::ESTATUS_MATERIAL_UBICADO,
        self::ESTATUS_CARGANDO,
        self::ESTATUS_CHECKLIST,
        self::ESTATUS_EVIDENCIA,
        self::ESTATUS_REMISION,
        self::ESTATUS_CERRADA,
    ];

    public static array $estatusFlujo = [
        'ARMADA' => 'Ruta armada',
        'MATERIAL_UBICADO' => 'Material ubicado',
        'CARGANDO' => 'Chofer cargando',
        'CHECKLIST_OK' => 'Checklist chofer OK',
        'EVIDENCIA' => 'Evidencia de carga',
        'REMISION_FIRMADA' => 'Remisiones firmadas',
        'CERRADA' => 'Salida / cerrada',
        'EN_RUTA' => 'En ruta (legado)',
    ];

    protected $fillable = [
        'folio',
        'fecha',
        'estatus',
        'unidad',
        'camion_id',
        'chofer_nombre',
        'chofer_id',
        'chofer_tipo',
        'chofer_llegada_at',
        'check_seguro_vehiculo',
        'check_seguro_imss',
        'check_botas',
        'check_casco',
        'check_chaleco',
        'check_epp',
        'checklist_ok_at',
        'checklist_por',
        'evidencia_ok_at',
        'evidencia_por',
        'remision_ok_at',
        'salida_at',
        'salida_por',
        'observaciones',
        'creado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'chofer_llegada_at' => 'datetime',
        'checklist_ok_at' => 'datetime',
        'evidencia_ok_at' => 'datetime',
        'remision_ok_at' => 'datetime',
        'salida_at' => 'datetime',
        'check_seguro_vehiculo' => 'boolean',
        'check_seguro_imss' => 'boolean',
        'check_botas' => 'boolean',
        'check_casco' => 'boolean',
        'check_chaleco' => 'boolean',
        'check_epp' => 'boolean',
    ];

    public function cargas(): HasMany
    {
        return $this->hasMany(Carga::class, 'ruta_id');
    }

    public function camion(): BelongsTo
    {
        return $this->belongsTo(Camion::class, 'camion_id');
    }

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Chofer::class, 'chofer_id');
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(RutaCargaEvidencia::class, 'ruta_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function getEstatusTextoAttribute(): string
    {
        return self::$estatusFlujo[$this->estatus] ?? $this->estatus;
    }

    public function estaCerrada(): bool
    {
        return $this->estatus === self::ESTATUS_CERRADA;
    }

    public function checklistCompleto(): bool
    {
        if ($this->chofer_tipo === self::CHOFER_EXTERNO) {
            return $this->check_seguro_vehiculo
                && $this->check_seguro_imss
                && $this->check_botas
                && $this->check_casco
                && $this->check_chaleco;
        }

        if ($this->chofer_tipo === self::CHOFER_INTERNO) {
            return (bool) $this->check_epp;
        }

        return false;
    }

    public function remisionesCompletas(): bool
    {
        $cargas = $this->cargas;
        if ($cargas->isEmpty()) {
            return false;
        }

        return $cargas->every(fn (Carga $c) => (bool) $c->remision_firmada);
    }

    /**
     * Recalcula orden_entrega (si se pide por distancia) y orden_carga (inverso).
     */
    public function recalcularOrdenes(bool $ordenarPorDistancia = false): void
    {
        $cargas = $this->cargas()->get();
        if ($cargas->isEmpty()) {
            return;
        }

        if ($ordenarPorDistancia) {
            $cargas = $cargas->sortBy(function (Carga $c) {
                $km = $c->distancia_km;
                return $km === null ? PHP_FLOAT_MAX : (float) $km;
            })->values();

            foreach ($cargas as $i => $carga) {
                $carga->orden_entrega = $i + 1;
            }
        } else {
            $cargas = $cargas->sortBy(function (Carga $c) {
                return $c->orden_entrega ?? PHP_INT_MAX;
            })->values();

            foreach ($cargas as $i => $carga) {
                if (empty($carga->orden_entrega)) {
                    $carga->orden_entrega = $i + 1;
                }
            }

            $cargas = $cargas->sortBy('orden_entrega')->values();
            foreach ($cargas as $i => $carga) {
                $carga->orden_entrega = $i + 1;
            }
        }

        $total = $cargas->count();
        foreach ($cargas as $carga) {
            $carga->orden_carga = $total - (int) $carga->orden_entrega + 1;
            $carga->save();
        }
    }

    /**
     * Resumen de capacidad del camión vs metros de las cargas de la ruta.
     *
     * @return array{
     *   metros_usados: float,
     *   capacidad_metros: float|null,
     *   metros_disponibles: float|null,
     *   porcentaje: float|null,
     *   cabe: bool|null,
     *   mensaje: string
     * }
     */
    public function resumenCapacidad(): array
    {
        $cargas = $this->relationLoaded('cargas')
            ? $this->cargas
            : $this->cargas()->with(['ordenProduccion.detalles', 'pedido.detalles'])->get();

        $metros = round($cargas->sum(fn (Carga $c) => $c->metrosCarga()), 2);
        $camion = $this->relationLoaded('camion') ? $this->camion : $this->camion()->first();
        $capacidad = $camion && $camion->capacidad_metros !== null
            ? (float) $camion->capacidad_metros
            : null;

        if ($capacidad === null || $capacidad <= 0) {
            return [
                'metros_usados' => $metros,
                'capacidad_metros' => null,
                'metros_disponibles' => null,
                'porcentaje' => null,
                'cabe' => null,
                'mensaje' => $camion
                    ? 'El camión no tiene capacidad en metros configurada. Capture capacidad (m) en el catálogo.'
                    : 'Seleccione un camión con capacidad en metros para validar si cabe la carga.',
            ];
        }

        $disponible = round($capacidad - $metros, 2);
        $porcentaje = $capacidad > 0 ? round(($metros / $capacidad) * 100, 1) : null;
        $cabe = $metros <= $capacidad;

        return [
            'metros_usados' => $metros,
            'capacidad_metros' => $capacidad,
            'metros_disponibles' => $disponible,
            'porcentaje' => $porcentaje,
            'cabe' => $cabe,
            'mensaje' => $cabe
                ? 'Sí caben: ' . number_format($metros, 1) . ' m de ' . number_format($capacidad, 1) . ' m (' . number_format($disponible, 1) . ' m libres).'
                : 'No caben: ' . number_format($metros, 1) . ' m exceden la capacidad de ' . number_format($capacidad, 1) . ' m (sobran ' . number_format(abs($disponible), 1) . ' m).',
        ];
    }
}
