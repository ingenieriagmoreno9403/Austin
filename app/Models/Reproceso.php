<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Reproceso extends Model
{
    protected $table = 'tbl_reprocesos';

    public const ORIGEN_CALIDAD = 'CALIDAD';
    public const ORIGEN_EXTRUSION = 'EXTRUSION';

    public const ESTATUS_DISPONIBLE = 'DISPONIBLE';
    public const ESTATUS_CORTADA = 'CORTADA';
    public const ESTATUS_PESADA = 'PESADA';
    public const ESTATUS_REPORTADA = 'REPORTADA';
    public const ESTATUS_TRITURADA = 'TRITURADA';
    public const ESTATUS_SACA_PESADA = 'SACA_PESADA';
    public const ESTATUS_IDENTIFICADA = 'IDENTIFICADA';
    public const ESTATUS_LISTO_PELETIZADO = 'LISTO_PELETIZADO';
    public const ESTATUS_EN_PELETIZADO = 'EN_PELETIZADO';
    public const ESTATUS_PELETIZADO = 'PELETIZADO';
    public const ESTATUS_SACA_RESINA_DESMONTADA = 'SACA_RESINA_DESMONTADA';
    public const ESTATUS_RESINA_IDENTIFICADA = 'RESINA_IDENTIFICADA';
    public const ESTATUS_SACA_RESINA_PESADA = 'SACA_RESINA_PESADA';
    public const ESTATUS_RESINA_ALMACENADA = 'RESINA_ALMACENADA';
    public const ESTATUS_PRODUCCION_REPORTADA = 'PRODUCCION_REPORTADA';
    public const ESTATUS_KILOS_RESINA_SISTEMA = 'KILOS_RESINA_SISTEMA';
    public const ESTATUS_STOCK_RESINA = 'STOCK_RESINA';
    public const ESTATUS_CANCELADA = 'CANCELADA';

    public const UBICACION_FOLIO = 'RECHAZADOS POR CALIDAD';
    public const UBICACION_MATERIAL_TRITURADO = 'MATERIAL TRITURADO';
    public const UBICACION_PELETIZADO = 'PELETIZADO';
    public const UBICACION_NAVE2_RESINA = 'NAVE 2 RESINA';

    public const DOC_ETIQUETA_TRIT = 'IOH-EQT-TRIT-01';
    public const DOC_REPORTE_TRIT = 'IOH-RDPT-TRIT-01';
    public const DOC_REPORTE_PELLET = 'IOH-RPDP-PELLET-01';
    public const DOC_ETIQUETA_RESINA = 'IOH-EMP-PELLET-01';
    public const DOC_REPORTE_PRODUCCION = 'REPORTE DE PRODUCCION';

    public static array $origenes = [
        self::ORIGEN_CALIDAD => 'Rechazo por calidad',
        self::ORIGEN_EXTRUSION => 'Merma / extrusión',
    ];

    public static array $estatuses = [
        self::ESTATUS_DISPONIBLE => 'Disponible para reprocesar',
        self::ESTATUS_CORTADA => 'Cortada',
        self::ESTATUS_PESADA => 'Pesada',
        self::ESTATUS_REPORTADA => 'Reportada',
        self::ESTATUS_TRITURADA => 'Triturada',
        self::ESTATUS_SACA_PESADA => 'Saca triturado pesada',
        self::ESTATUS_IDENTIFICADA => 'Material identificado',
        self::ESTATUS_LISTO_PELETIZADO => 'Listo en área peletizado',
        self::ESTATUS_EN_PELETIZADO => 'En peletizado',
        self::ESTATUS_PELETIZADO => 'Peletizado',
        self::ESTATUS_SACA_RESINA_DESMONTADA => 'Saca resina desmontada',
        self::ESTATUS_RESINA_IDENTIFICADA => 'Resina identificada',
        self::ESTATUS_SACA_RESINA_PESADA => 'Saca resina pesada',
        self::ESTATUS_RESINA_ALMACENADA => 'Resina en almacén',
        self::ESTATUS_PRODUCCION_REPORTADA => 'Kilos en reporte producción',
        self::ESTATUS_KILOS_RESINA_SISTEMA => 'Kilos resina en sistema',
        self::ESTATUS_STOCK_RESINA => 'Stock de resina generado',
        self::ESTATUS_CANCELADA => 'Cancelada',
    ];

    public static array $flujo = [
        self::ESTATUS_DISPONIBLE,
        self::ESTATUS_CORTADA,
        self::ESTATUS_PESADA,
        self::ESTATUS_REPORTADA,
        self::ESTATUS_TRITURADA,
        self::ESTATUS_SACA_PESADA,
        self::ESTATUS_IDENTIFICADA,
        self::ESTATUS_LISTO_PELETIZADO,
        self::ESTATUS_EN_PELETIZADO,
        self::ESTATUS_PELETIZADO,
        self::ESTATUS_SACA_RESINA_DESMONTADA,
        self::ESTATUS_RESINA_IDENTIFICADA,
        self::ESTATUS_SACA_RESINA_PESADA,
        self::ESTATUS_RESINA_ALMACENADA,
        self::ESTATUS_PRODUCCION_REPORTADA,
        self::ESTATUS_KILOS_RESINA_SISTEMA,
        self::ESTATUS_STOCK_RESINA,
    ];

    public static array $pasosTexto = [
        self::ESTATUS_DISPONIBLE => 'Colocar tubería a disposición (salida rechazada por calidad en la OP). Seleccionar la máquina de reproceso (triturado/peletizado).',
        self::ESTATUS_CORTADA => 'Cortar la tubería en pedazos manejables para llevarla a triturado.',
        self::ESTATUS_PESADA => 'Pesar la tubería en báscula e registrar los kilos.',
        self::ESTATUS_REPORTADA => 'Supervisor reporta la cantidad (kg) a reprocesar (reporte de producción diaria).',
        self::ESTATUS_TRITURADA => 'Llevar a triturado, triturar y embolsar el material.',
        self::ESTATUS_SACA_PESADA => 'Pesar sacos con material triturado. Pegar y completar etiqueta ' . self::DOC_ETIQUETA_TRIT . '.',
        self::ESTATUS_IDENTIFICADA => 'Identificar material triturado y registrar datos de la saca en ' . self::DOC_REPORTE_TRIT . '.',
        self::ESTATUS_LISTO_PELETIZADO => 'Registrar en material triturado y colocar la saca cerca del área de peletizado.',
        self::ESTATUS_EN_PELETIZADO => 'Trasladar saca al área de peletizado y registrar inicio en ' . self::DOC_REPORTE_PELLET . '.',
        self::ESTATUS_PELETIZADO => 'Peletizar el material triturado.',
        self::ESTATUS_SACA_RESINA_DESMONTADA => 'Desmontar saca terminada con resina y pegar etiqueta ' . self::DOC_ETIQUETA_RESINA . '.',
        self::ESTATUS_RESINA_IDENTIFICADA => 'Identificar la materia / tipo de resina en el saco.',
        self::ESTATUS_SACA_RESINA_PESADA => 'Pesar saco en báscula y anotar peso en ' . self::DOC_ETIQUETA_RESINA . ' y ' . self::DOC_REPORTE_PELLET . '.',
        self::ESTATUS_RESINA_ALMACENADA => 'Colocar saca peletizada/resina en Nave dos, área según tipo de resina.',
        self::ESTATUS_PRODUCCION_REPORTADA => 'Supervisor registra kilos producidos por resina en el ' . self::DOC_REPORTE_PRODUCCION . '.',
        self::ESTATUS_KILOS_RESINA_SISTEMA => 'Auxiliar administrativo: transformar virtualmente los kilos de retrabajo a resina producida en planta.',
        self::ESTATUS_STOCK_RESINA => 'Auxiliar de almacén: pasar kilos de resina del almacén de proceso al almacén IOHISA (generar stock).',
    ];

    protected $fillable = [
        'folio',
        'origen',
        'estatus',
        'orden_id',
        'orden_detalle_id',
        'salida_id',
        'inspeccion_id',
        'ubicacion_id',
        'maquina_id',
        'maquina_peletizado_id',
        'producto_id',
        'producto_resina_id',
        'metros',
        'kg_estimado',
        'kg_pesado',
        'kg_reportado',
        'kg_saca_triturada',
        'etiqueta_triturado',
        'reporte_triturado',
        'identificacion_material',
        'reporte_peletizado',
        'etiqueta_resina',
        'identificacion_resina',
        'kg_saca_resina',
        'kg_produccion_reportado',
        'kg_resina_sistema',
        'observaciones',
        'creado_por',
        'reportado_por',
        'reportado_at',
        'triturado_por',
        'triturado_at',
        'peletizado_por',
        'peletizado_inicio_at',
        'peletizado_fin_at',
        'produccion_reportado_por',
        'produccion_reportado_at',
        'kilos_sistema_por',
        'kilos_sistema_at',
        'stock_por',
        'stock_at',
    ];

    protected $casts = [
        'metros' => 'decimal:3',
        'kg_estimado' => 'decimal:3',
        'kg_pesado' => 'decimal:3',
        'kg_reportado' => 'decimal:3',
        'kg_saca_triturada' => 'decimal:3',
        'kg_saca_resina' => 'decimal:3',
        'kg_produccion_reportado' => 'decimal:3',
        'kg_resina_sistema' => 'decimal:3',
        'reportado_at' => 'datetime',
        'triturado_at' => 'datetime',
        'peletizado_inicio_at' => 'datetime',
        'peletizado_fin_at' => 'datetime',
        'produccion_reportado_at' => 'datetime',
        'kilos_sistema_at' => 'datetime',
        'stock_at' => 'datetime',
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

    public function inspeccion(): BelongsTo
    {
        return $this->belongsTo(InspeccionCalidadOrdenTrabajo::class, 'inspeccion_id');
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicaciones::class, 'ubicacion_id');
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function maquinaPeletizado(): BelongsTo
    {
        return $this->belongsTo(Maquina::class, 'maquina_peletizado_id');
    }

    public function productoResina(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_resina_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function reportadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reportado_por');
    }

    public function getOrigenTextoAttribute(): string
    {
        return self::$origenes[$this->origen] ?? (string) $this->origen;
    }

    public function getEstatusTextoAttribute(): string
    {
        return self::$estatuses[$this->estatus] ?? (string) $this->estatus;
    }

    public function getSiguienteEstatusAttribute(): ?string
    {
        $idx = array_search($this->estatus, self::$flujo, true);
        if ($idx === false) {
            return null;
        }

        return self::$flujo[$idx + 1] ?? null;
    }

    public function estaCerrada(): bool
    {
        return in_array($this->estatus, [self::ESTATUS_STOCK_RESINA, self::ESTATUS_CANCELADA], true);
    }

    public function tieneMaquina(): bool
    {
        return !empty($this->maquina_id);
    }

    public function tieneMaquinaPeletizado(): bool
    {
        return !empty($this->maquina_peletizado_id);
    }

    public static function ubicacionPorFolio(string $folio): ?Ubicaciones
    {
        return Ubicaciones::query()
            ->whereRaw('UPPER(folio_interno) = ?', [strtoupper($folio)])
            ->first();
    }

    public static function ubicacionRechazados(): ?Ubicaciones
    {
        return self::ubicacionPorFolio(self::UBICACION_FOLIO);
    }

    public static function ubicacionMaterialTriturado(): ?Ubicaciones
    {
        return self::ubicacionPorFolio(self::UBICACION_MATERIAL_TRITURADO);
    }

    public static function ubicacionPeletizado(): ?Ubicaciones
    {
        return self::ubicacionPorFolio(self::UBICACION_PELETIZADO);
    }

    public static function ubicacionNave2Resina(): ?Ubicaciones
    {
        return self::ubicacionPorFolio(self::UBICACION_NAVE2_RESINA);
    }

    public static function generarFolio(): string
    {
        $prefijo = 'REP-' . now()->format('Ymd') . '-';
        $ultimo = self::where('folio', 'like', $prefijo . '%')
            ->orderByDesc('id')
            ->value('folio');
        $n = 1;
        if ($ultimo && preg_match('/-(\d+)$/', $ultimo, $m)) {
            $n = ((int) $m[1]) + 1;
        }

        return $prefijo . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    public static function crearDesdeRechazoCalidad(
        OrdenProduccionDetalleSalida $salida,
        InspeccionCalidadOrdenTrabajo $inspeccion,
        OrdenProduccion $orden
    ): self {
        if (!$orden->id || !$salida->id) {
            throw new \InvalidArgumentException('El reproceso solo puede crearse desde una salida rechazada de una OP.');
        }

        if ($inspeccion->resultado !== InspeccionCalidadOrdenTrabajo::RESULTADO_RECHAZADO) {
            throw new \InvalidArgumentException('La inspección debe estar rechazada para generar reproceso.');
        }

        $existente = self::where('salida_id', $salida->id)->first();
        if ($existente) {
            return $existente;
        }

        $ubicacion = self::ubicacionRechazados();
        if ($ubicacion && empty($salida->ubicacion_destino_id)) {
            $salida->ubicacion_destino_id = $ubicacion->id;
            $salida->save();
        }

        $detalle = $salida->detalle;
        $kg = $salida->kg_real ?? $salida->kg_merma ?? $inspeccion->kg_real ?? $inspeccion->kg_merma;

        return self::create([
            'folio' => self::generarFolio(),
            'origen' => self::ORIGEN_CALIDAD,
            'estatus' => self::ESTATUS_DISPONIBLE,
            'orden_id' => $orden->id,
            'orden_detalle_id' => $salida->orden_detalle_id,
            'salida_id' => $salida->id,
            'inspeccion_id' => $inspeccion->id,
            'ubicacion_id' => $ubicacion?->id,
            'producto_id' => $detalle->producto_id ?? null,
            'metros' => $salida->metros ?? $inspeccion->metros,
            'kg_estimado' => $kg,
            'observaciones' => $inspeccion->observaciones,
            'creado_por' => auth()->id(),
        ]);
    }

    public static function sincronizarRechazosPendientes(): int
    {
        $creados = 0;
        $ubicacion = self::ubicacionRechazados();

        $salidas = OrdenProduccionDetalleSalida::query()
            ->whereHas('inspeccion', function ($q) {
                $q->where('resultado', InspeccionCalidadOrdenTrabajo::RESULTADO_RECHAZADO);
            })
            ->whereDoesntHave('reproceso')
            ->with(['inspeccion', 'detalle.orden'])
            ->limit(200)
            ->get();

        foreach ($salidas as $salida) {
            $insp = $salida->inspeccion;
            $orden = $salida->detalle?->orden;
            if (!$insp || !$orden) {
                continue;
            }

            DB::transaction(function () use ($salida, $insp, $orden, $ubicacion, &$creados) {
                if ($ubicacion && empty($salida->ubicacion_destino_id)) {
                    $salida->ubicacion_destino_id = $ubicacion->id;
                    $salida->save();
                }
                self::crearDesdeRechazoCalidad($salida, $insp, $orden);
                $creados++;
            });
        }

        return $creados;
    }
}
