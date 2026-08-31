<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspeccionCalidadOrdenTrabajo extends Model
{
    protected $table = 'inspeccion_de_calidad_por_ordenes_de_trabajo';

    public const RESULTADO_ACEPTADO = 'ACEPTADO';
    public const RESULTADO_RECHAZADO = 'RECHAZADO';
    public const RESULTADO_PENDIENTE = 'PENDIENTE';

    /** Tolerancia temporal uniforme: ±4% sobre el valor nominal (todos los controles). */
    public const TOLERANCIA_ERROR_PCT = 4.0;
    public const TOLERANCIA_MIN_PCT = -4.0;
    public const TOLERANCIA_MAX_PCT = 4.0;

    public const ESTATUS_MEDICION = 'MEDICION_ESPESORES';
    public const ESTATUS_AJUSTAR = 'AJUSTAR_PARAMETROS';
    public const ESTATUS_CO_EXTRUSORA = 'CO_EXTRUSORA';
    public const ESTATUS_TATUADORA = 'TATUADORA';
    public const ESTATUS_ACEPTADO = 'ACEPTADO';
    public const ESTATUS_RECHAZADO = 'RECHAZADO';

    public static array $estatusCalidad = [
        self::ESTATUS_MEDICION => 'Medición de espesores (8 puntos)',
        self::ESTATUS_AJUSTAR => 'Ajustar extrusor / jalador',
        self::ESTATUS_CO_EXTRUSORA => 'Encender co-extrusora',
        self::ESTATUS_TATUADORA => 'Encender tatuadora',
        self::ESTATUS_ACEPTADO => 'Calidad aceptada',
        self::ESTATUS_RECHAZADO => 'Rechazado / merma',
    ];

    public static array $coloresLinea = [
        'AZUL' => 'Azul',
        'AMARILLO' => 'Amarillo',
        'MORADO' => 'Morado',
        'ROJO' => 'Rojo',
    ];

    protected $fillable = [
        'orden_id',
        'orden_detalle_id',
        'salida_id',
        'resultado',
        'estatus_calidad',
        'espesor_nominal',
        'espesor_min',
        'espesor_max',
        'espesor_1',
        'espesor_2',
        'espesor_3',
        'espesor_4',
        'espesor_5',
        'espesor_6',
        'espesor_7',
        'espesor_8',
        'espesores_en_rango',
        'color_linea',
        'co_extrusora_ok',
        'tatuadora_ok',
        'tatuaje_diametro',
        'tatuaje_rd',
        'tatuaje_lote',
        'tatuaje_fecha',
        'tatuaje_hora',
        'diametro_real',
        'rd_real',
        'espesor_real',
        'metros',
        'kg_real',
        'kg_teorico',
        'kg_merma',
        'porcentaje_merma',
        'observaciones',
        'auditor_id',
        'inspeccionado_at',
    ];

    protected $casts = [
        'espesor_nominal' => 'decimal:4',
        'espesor_min' => 'decimal:4',
        'espesor_max' => 'decimal:4',
        'espesor_1' => 'decimal:4',
        'espesor_2' => 'decimal:4',
        'espesor_3' => 'decimal:4',
        'espesor_4' => 'decimal:4',
        'espesor_5' => 'decimal:4',
        'espesor_6' => 'decimal:4',
        'espesor_7' => 'decimal:4',
        'espesor_8' => 'decimal:4',
        'espesores_en_rango' => 'boolean',
        'co_extrusora_ok' => 'boolean',
        'tatuadora_ok' => 'boolean',
        'espesor_real' => 'decimal:3',
        'metros' => 'decimal:3',
        'kg_real' => 'decimal:3',
        'kg_teorico' => 'decimal:3',
        'kg_merma' => 'decimal:3',
        'porcentaje_merma' => 'decimal:3',
        'tatuaje_fecha' => 'date',
        'inspeccionado_at' => 'datetime',
    ];

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenProduccion::class, 'orden_id');
    }

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(OrdenProduccionDetalle::class, 'orden_detalle_id');
    }

    public function salida(): BelongsTo
    {
        return $this->belongsTo(OrdenProduccionDetalleSalida::class, 'salida_id');
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function esAceptado(): bool
    {
        return $this->resultado === self::RESULTADO_ACEPTADO
            || $this->estatus_calidad === self::ESTATUS_ACEPTADO;
    }

    public function getResultadoTextoAttribute(): string
    {
        return self::$estatusCalidad[$this->estatus_calidad]
            ?? match ($this->resultado) {
                self::RESULTADO_ACEPTADO => 'Aceptado',
                self::RESULTADO_RECHAZADO => 'Rechazado',
                default => $this->resultado ?: 'Pendiente',
            };
    }

    public function getEstatusCalidadTextoAttribute(): string
    {
        return self::$estatusCalidad[$this->estatus_calidad] ?? ($this->estatus_calidad ?: '—');
    }

    /**
     * @return array{min: float, max: float}|null
     */
    public static function rangoEspesor(?float $nominal): ?array
    {
        if ($nominal === null || $nominal <= 0) {
            return null;
        }

        return [
            'min' => round($nominal * (1 + self::TOLERANCIA_MIN_PCT / 100), 4),
            'max' => round($nominal * (1 + self::TOLERANCIA_MAX_PCT / 100), 4),
        ];
    }

    /**
     * @param  array<int, float|null>  $medidas
     */
    public static function espesoresDentroDeRango(array $medidas, float $min, float $max): bool
    {
        if (count($medidas) < 8) {
            return false;
        }

        foreach ($medidas as $valor) {
            if ($valor === null || $valor <= 0) {
                return false;
            }
            if ($valor < $min || $valor > $max) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, float|null>
     */
    public function medidasEspesor(): array
    {
        $out = [];
        for ($i = 1; $i <= 8; $i++) {
            $val = $this->{'espesor_' . $i};
            $out[$i] = $val !== null ? (float) $val : null;
        }

        return $out;
    }
}
