<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenProduccion extends Model
{
    protected $table = 'tbl_ordenes';

    public $timestamps = false;

    const ESTATUS_PREPARANDO_MAQUINAS = 'PREPARANDO_MAQUINAS';
    const ESTATUS_CALENTANDO = 'CALENTANDO_MAQUINA';
    const ESTATUS_EN_ESPERA_MATERIALES = 'EN_ESPERA_MATERIALES';
    const ESTATUS_EN_PRODUCCION = 'EN_PRODUCCION';
    const ESTATUS_PAUSADA = 'PAUSADA';
    const ESTATUS_REVISION_CALIDAD = 'REVISION_CALIDAD';
    const ESTATUS_PREPARAR_EMPAQUE = 'PREPARAR_EMPAQUE';
    const ESTATUS_ENROLLANDO = 'ENROLLANDO';
    const ESTATUS_A_LONGITUD = 'A_LONGITUD';
    const ESTATUS_CORTAR_FLEJAR = 'CORTAR_FLEJAR';
    const ESTATUS_EMPACANDO = 'EMPACANDO';
    const ESTATUS_RUTA_TORNO = 'RUTA_TORNO';
    const ESTATUS_RUTA_TORNO_EXTERNO = 'RUTA_TORNO_EXTERNO';
    const ESTATUS_RUTA_CORTE = 'RUTA_CORTE';
    const ESTATUS_CALIDAD_FINAL = 'CALIDAD_FINAL';
    const ESTATUS_ENTREGA_ALMACEN = 'ENTREGA_ALMACEN';
    const ESTATUS_TERMINADA = 'TERMINADA';
    const ESTATUS_CANCELADA = 'CANCELADA';
    const ESTATUS_RECHAZADA_POR_CALIDAD = 'RECHAZADA_POR_CALIDAD';

    public const TIPO_PROCESO_TUBO = 'TUBO';
    public const TIPO_PROCESO_FLANGE = 'FLANGE';
    public const TIPO_PROCESO_CONEXION = 'CONEXION';

    public const RUTA_FLANGE_TORNO = 'TORNO';
    public const RUTA_FLANGE_TORNO_EXTERNO = 'TORNO_EXTERNO';
    public const RUTA_FLANGE_CORTE = 'CORTE';

    public const TIPO_EMPAQUE_ROLLO = 'ROLLO';
    public const TIPO_EMPAQUE_TRAMOS = 'TRAMOS';

    /**
     * Etiquetas del seguimiento (procedimiento P-FT-PDN-0001 simplificado).
     */
    public static array $estatusFlujo = [
        'EN_ESPERA_MATERIALES' => 'En espera de materiales',
        'PREPARANDO_MAQUINAS' => 'Preparando máquinas',
        'CALENTANDO_MAQUINA' => 'Llenando tinas',
        'EN_PRODUCCION' => 'En producción',
        'PAUSADA' => 'Pausada',
        'REVISION_CALIDAD' => 'Revisión de calidad',
        'PREPARAR_EMPAQUE' => 'Preparar empaque',
        'ENROLLANDO' => 'Enrollando',
        'A_LONGITUD' => 'A longitud requerida',
        'CORTAR_FLEJAR' => 'Cortar y flejar',
        'EMPACANDO' => 'Empacando',
        'RUTA_TORNO' => 'Torno (flange 4")',
        'RUTA_TORNO_EXTERNO' => 'Torno externo (RD especial)',
        'RUTA_CORTE' => 'Corte tapa (flange 6")',
        'CALIDAD_FINAL' => 'Calidad final flange',
        'ENTREGA_ALMACEN' => 'Entrega a almacén',
        'TERMINADA' => 'Terminada',
        'CANCELADA' => 'Cancelada',
        'RECHAZADA_POR_CALIDAD' => 'Rechazada por calidad',
    ];

    public static array $tiposEmpaque = [
        self::TIPO_EMPAQUE_ROLLO => 'Rollo (> 50 m)',
        self::TIPO_EMPAQUE_TRAMOS => 'Tramos (≤ 20 m)',
    ];

    public static array $estatusAyuda = [
        'EN_ESPERA_MATERIALES' => 'Falta materia prima. Use «Ingresar materiales» cuando haya stock y adjunte la orden al almacenista.',
        'PREPARANDO_MAQUINAS' => 'Recibir OP, informar operadores, preparar máquina.',
        'CALENTANDO_MAQUINA' => 'Alcanzar temperatura de dado (175–190 °C) y llenar tinas de vacío / enfriamiento.',
        'EN_PRODUCCION' => 'Tubo: extrusión. Flange: inyección. Conexión: fabricación de piezas (termofusión / inyección según tipo).',
        'PAUSADA' => 'Producción detenida temporalmente.',
        'REVISION_CALIDAD' => 'Tubo: 8 espesores/tatuadora. Flange: post-inyección. Conexión: inspección de piezas.',
        'PREPARAR_EMPAQUE' => 'Definir si va en rollo o tramos y acercar enrollador o camas a la línea.',
        'ENROLLANDO' => 'Sujetar tubería al enrollador y enrollar (solo si es rollo).',
        'A_LONGITUD' => 'Esperar/medir hasta la longitud requerida (contador o flexómetro).',
        'CORTAR_FLEJAR' => 'Cortar (guillotina/sierra) y flejar el paquete o rollo.',
        'EMPACANDO' => 'Empaque en curso (legado).',
        'RUTA_TORNO' => 'Maquinado en torno (flange/conexión según Ø y RD).',
        'RUTA_TORNO_EXTERNO' => 'RD 6 / 7 / 7.3: enviar a proveedor externo para maquinado.',
        'RUTA_CORTE' => 'Corte / terminado (p. ej. tapa o acabado por termofusión).',
        'CALIDAD_FINAL' => 'Inspección final: piezas sin imperfecciones.',
        'ENTREGA_ALMACEN' => 'Entregar piezas liberadas a almacén; reportar buenas/malas y kg de retrabajo.',
        'TERMINADA' => 'Orden cerrada.',
        'CANCELADA' => 'Orden cancelada.',
        'RECHAZADA_POR_CALIDAD' => 'Proceso terminado: pieza enviada a inventario de merma (RECHAZADOS POR CALIDAD).',
    ];

    const ESTATUS_ACTIVAS = [
        'PREPARANDO_MAQUINAS',
        'CALENTANDO_MAQUINA',
        'EN_ESPERA_MATERIALES',
        'EN_PRODUCCION',
        'PAUSADA',
        'REVISION_CALIDAD',
        'PREPARAR_EMPAQUE',
        'ENROLLANDO',
        'A_LONGITUD',
        'CORTAR_FLEJAR',
        'EMPACANDO',
        'RUTA_TORNO',
        'RUTA_TORNO_EXTERNO',
        'RUTA_CORTE',
        'CALIDAD_FINAL',
        'ENTREGA_ALMACEN',
    ];

    const ESTATUS_FINALES = ['TERMINADA', 'CANCELADA', 'RECHAZADA_POR_CALIDAD'];

    const ESTATUS_EMPAQUE = [
        'PREPARAR_EMPAQUE',
        'ENROLLANDO',
        'A_LONGITUD',
        'CORTAR_FLEJAR',
    ];

    public static array $secuenciaFlujo = [
        'EN_ESPERA_MATERIALES',
        'PREPARANDO_MAQUINAS',
        'CALENTANDO_MAQUINA',
        'EN_PRODUCCION',
        'REVISION_CALIDAD',
        'PREPARAR_EMPAQUE',
        'ENROLLANDO',
        'A_LONGITUD',
        'CORTAR_FLEJAR',
        'TERMINADA',
    ];

    /** Flujo flange (inyección → calidad → torno/corte → calidad final → almacén). */
    public static array $secuenciaFlujoFlange = [
        'EN_ESPERA_MATERIALES',
        'PREPARANDO_MAQUINAS',
        'EN_PRODUCCION',
        'REVISION_CALIDAD',
        'RUTA_TORNO',
        'CALIDAD_FINAL',
        'ENTREGA_ALMACEN',
        'TERMINADA',
    ];

    const ESTATUS_AVANCE_MANUAL = [
        'PREPARANDO_MAQUINAS',
        'CALENTANDO_MAQUINA',
        'EN_PRODUCCION',
        'RUTA_TORNO',
        'RUTA_TORNO_EXTERNO',
        'RUTA_CORTE',
        'CALIDAD_FINAL',
        'ENTREGA_ALMACEN',
    ];

    protected $fillable = [
        'folio',
        'fecha',
        'pedido_id',
        'maquina_id',
        'turno_id',
        'operador_id',
        'supervisor_id',
        'estatus',
        'tipo_proceso',
        'ruta_flange',
        'observaciones',
        'tipo_empaque',
        'longitud_objetivo_m',
        'nave_destino',
        'ubicacion_destino_id',
        'orden_materiales_ruta',
        'orden_materiales_nombre',
        'archivo_op_ruta',
        'archivo_op_nombre',
        'materiales_tomados_at',
        'materiales_tomados_by',
        'motivo_cancelacion_id',
        'motivo_cancelacion_nota',
        'cancelada_por',
        'cancelada_at',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'longitud_objetivo_m' => 'decimal:3',
        'materiales_tomados_at' => 'datetime',
        'cancelada_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(VentaPedido::class, 'pedido_id');
    }

    public function ubicacionDestino()
    {
        return $this->belongsTo(Ubicaciones::class, 'ubicacion_destino_id');
    }

    public function etiquetaNaveDestino(): string
    {
        if ($this->relationLoaded('ubicacionDestino') && $this->ubicacionDestino) {
            $u = $this->ubicacionDestino;
            return trim(($u->folio_interno ?? '') . ($u->descripcion ? ' — ' . $u->descripcion : ''))
                ?: (string) ($this->nave_destino ?: '—');
        }

        if ($this->nave_destino) {
            return str_replace('_', ' ', (string) $this->nave_destino);
        }

        return '—';
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function registroProduccion()
    {
        return $this->hasOne(RegistroProduccion::class, 'orden_id');
    }

    public function paros()
    {
        return $this->hasMany(ParoProduccion::class, 'orden_id');
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }

    public function operador()
    {
        return $this->belongsTo(Empleados::class, 'operador_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(Empleados::class, 'supervisor_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function motivoCancelacion()
    {
        return $this->belongsTo(MotivoCancelacionProduccion::class, 'motivo_cancelacion_id');
    }

    public function canceladaPor()
    {
        return $this->belongsTo(User::class, 'cancelada_por');
    }

    public function detalles()
    {
        return $this->hasMany(OrdenProduccionDetalle::class, 'orden_id');
    }

    public function etapasMaquina()
    {
        return $this->hasMany(OrdenProduccionMaquina::class, 'orden_id')->orderBy('secuencia');
    }

    public function etapaActual()
    {
        return $this->hasOne(OrdenProduccionMaquina::class, 'orden_id')
            ->whereNotIn('estatus', self::ESTATUS_FINALES)
            ->orderBy('secuencia');
    }

    public function esActiva(): bool
    {
        return ! in_array($this->estatus, self::ESTATUS_FINALES, true);
    }

    public function esFlange(): bool
    {
        return in_array($this->tipo_proceso ?? self::TIPO_PROCESO_TUBO, [
            self::TIPO_PROCESO_FLANGE,
            self::TIPO_PROCESO_CONEXION,
        ], true);
    }

    public function esConexion(): bool
    {
        return ($this->tipo_proceso ?? '') === self::TIPO_PROCESO_CONEXION;
    }

    /** Solo brida/flange (no incluye conexiones). */
    public function esSoloFlange(): bool
    {
        return ($this->tipo_proceso ?? '') === self::TIPO_PROCESO_FLANGE;
    }

    public function getTipoProcesoTextoAttribute(): string
    {
        return match ($this->tipo_proceso ?? self::TIPO_PROCESO_TUBO) {
            self::TIPO_PROCESO_FLANGE => 'Flange',
            self::TIPO_PROCESO_CONEXION => 'Conexión',
            self::TIPO_PROCESO_TUBO => 'Tubo',
            default => (string) ($this->tipo_proceso ?? 'Tubo'),
        };
    }

    public function getTipoProcesoBadgeAttribute(): string
    {
        return match ($this->tipo_proceso ?? self::TIPO_PROCESO_TUBO) {
            self::TIPO_PROCESO_FLANGE => 'FLANGE',
            self::TIPO_PROCESO_CONEXION => 'CONEXIÓN',
            default => 'TUBO',
        };
    }

    /** Texto de ayuda del estatus según tubo / flange / conexión. */
    public function ayudaEstatusActual(): string
    {
        return $this->ayudaEstatusPara($this->estatus);
    }

    public function ayudaEstatusPara(?string $estatus = null): string
    {
        $estatus = $estatus ?? $this->estatus;

        if ($this->esConexion()) {
            return match ($estatus) {
                self::ESTATUS_EN_PRODUCCION => 'Fabricar conexiones (piezas). Use inyectora o termofusión según el tipo (codo, tee, brida, cople…).',
                self::ESTATUS_REVISION_CALIDAD => 'Inspección de piezas: malformaciones, burbujas, medidas Ø/RD.',
                self::ESTATUS_RUTA_TORNO => 'Maquinado / acabado en torno si aplica al tipo de conexión.',
                self::ESTATUS_RUTA_TORNO_EXTERNO => 'Enviar a proveedor externo para maquinado (Ø/RD especial).',
                self::ESTATUS_RUTA_CORTE => 'Corte / terminado por termofusión si aplica.',
                self::ESTATUS_CALIDAD_FINAL => 'Calidad final de conexiones: piezas sin imperfecciones.',
                self::ESTATUS_ENTREGA_ALMACEN => 'Entregar conexiones liberadas a almacén.',
                default => self::$estatusAyuda[$estatus] ?? '',
            };
        }

        if ($this->esSoloFlange()) {
            return match ($estatus) {
                self::ESTATUS_EN_PRODUCCION => 'Inyección de flanges (90% resina virgen + 10% pigmentada).',
                self::ESTATUS_REVISION_CALIDAD => 'Inspección post-inyección (malformaciones/burbujas).',
                self::ESTATUS_RUTA_CORTE => 'Cortar tapa del flange (termofusión).',
                self::ESTATUS_CALIDAD_FINAL => 'Calidad final flange: piezas sin imperfecciones.',
                self::ESTATUS_ENTREGA_ALMACEN => 'Entregar flanges liberados a almacén.',
                default => self::$estatusAyuda[$estatus] ?? '',
            };
        }

        return self::$estatusAyuda[$estatus] ?? '';
    }

    /** Etiqueta corta de un paso del flujo (chips), según tipo de OP. */
    public function etiquetaEstatusPara(string $estatus): string
    {
        if ($this->esConexion()) {
            return match ($estatus) {
                self::ESTATUS_EN_PRODUCCION => 'Fabricando conexiones',
                self::ESTATUS_REVISION_CALIDAD => 'Calidad de conexiones',
                self::ESTATUS_RUTA_TORNO => 'Torno / acabado',
                self::ESTATUS_RUTA_TORNO_EXTERNO => 'Torno externo',
                self::ESTATUS_RUTA_CORTE => 'Corte / termofusión',
                self::ESTATUS_CALIDAD_FINAL => 'Calidad final',
                default => self::$estatusFlujo[$estatus] ?? $estatus,
            };
        }

        if ($this->esSoloFlange()) {
            return match ($estatus) {
                self::ESTATUS_EN_PRODUCCION => 'Inyectando flange',
                self::ESTATUS_REVISION_CALIDAD => 'Calidad post-inyección',
                default => self::$estatusFlujo[$estatus] ?? $estatus,
            };
        }

        return self::$estatusFlujo[$estatus] ?? $estatus;
    }

    public function secuenciaActiva(): array
    {
        if (!$this->esFlange()) {
            return self::$secuenciaFlujo;
        }

        $seq = self::$secuenciaFlujoFlange;
        // Sustituir el placeholder RUTA_TORNO por la ruta real de la OP.
        $rutaEstatus = match ($this->ruta_flange) {
            self::RUTA_FLANGE_TORNO_EXTERNO => self::ESTATUS_RUTA_TORNO_EXTERNO,
            self::RUTA_FLANGE_CORTE => self::ESTATUS_RUTA_CORTE,
            default => self::ESTATUS_RUTA_TORNO,
        };

        return array_map(function ($e) use ($rutaEstatus) {
            return $e === self::ESTATUS_RUTA_TORNO ? $rutaEstatus : $e;
        }, $seq);
    }

    /**
     * Posición del estatus dentro de la secuencia lineal (o -1 si no aplica).
     */
    public function indiceFlujo(?string $estatus = null): int
    {
        $estatus = $estatus ?? $this->estatus;
        $idx = array_search($estatus, $this->secuenciaActiva(), true);

        return $idx === false ? -1 : (int) $idx;
    }

    /**
     * Estatus inmediato siguiente permitido (solo hacia adelante, sin brincar).
     * Devuelve null cuando no hay avance posible por esta vía.
     */
    public function siguienteEstatus(): ?string
    {
        if (in_array($this->estatus, self::ESTATUS_FINALES, true)) {
            return null;
        }

        if ($this->estatus === self::ESTATUS_PAUSADA) {
            return self::ESTATUS_EN_PRODUCCION;
        }

        if ($this->estatus === self::ESTATUS_EN_ESPERA_MATERIALES) {
            return null;
        }

        // Flange: tras calidad post-inyección, ir a la ruta según diámetro/RD.
        if ($this->esFlange() && $this->estatus === self::ESTATUS_REVISION_CALIDAD) {
            return match ($this->ruta_flange) {
                self::RUTA_FLANGE_TORNO_EXTERNO => self::ESTATUS_RUTA_TORNO_EXTERNO,
                self::RUTA_FLANGE_CORTE => self::ESTATUS_RUTA_CORTE,
                default => self::ESTATUS_RUTA_TORNO,
            };
        }

        // Tubo: ramificación de empaque (sin enrollador → longitud).
        if (!$this->esFlange()
            && $this->estatus === self::ESTATUS_PREPARAR_EMPAQUE
            && $this->tipo_empaque
            && !TipoEmpaque::requiereEnrollar($this->tipo_empaque)) {
            return self::ESTATUS_A_LONGITUD;
        }

        $seq = $this->secuenciaActiva();
        $idx = array_search($this->estatus, $seq, true);
        if ($idx === false) {
            return null;
        }

        return $seq[$idx + 1] ?? null;
    }

    /**
     * ¿El avance de este estatus se hace con el selector genérico del detalle?
     */
    public function usaAvanceManual(): bool
    {
        if ($this->esFlange()) {
            return in_array($this->estatus, [
                self::ESTATUS_PREPARANDO_MAQUINAS,
                self::ESTATUS_EN_PRODUCCION,
                self::ESTATUS_RUTA_TORNO,
                self::ESTATUS_RUTA_TORNO_EXTERNO,
                self::ESTATUS_RUTA_CORTE,
                self::ESTATUS_CALIDAD_FINAL,
                self::ESTATUS_ENTREGA_ALMACEN,
            ], true);
        }

        return in_array($this->estatus, [
            self::ESTATUS_PREPARANDO_MAQUINAS,
            self::ESTATUS_CALENTANDO,
            self::ESTATUS_EN_PRODUCCION,
        ], true);
    }

    public function getEstatusTextoAttribute(): string
    {
        if ($this->esConexion() && $this->estatus === self::ESTATUS_EN_PRODUCCION) {
            return 'Fabricando conexiones';
        }
        if ($this->esConexion() && $this->estatus === self::ESTATUS_REVISION_CALIDAD) {
            return 'Calidad de conexiones';
        }
        if ($this->esSoloFlange() && $this->estatus === self::ESTATUS_EN_PRODUCCION) {
            return 'Inyectando flange';
        }
        if ($this->esSoloFlange() && $this->estatus === self::ESTATUS_REVISION_CALIDAD) {
            return 'Calidad post-inyección';
        }

        return self::$estatusFlujo[$this->estatus]
            ?? match ($this->estatus) {
                'ABIERTA' => 'Calentando máquina',
                'EN_PROCESO' => 'En producción',
                'CERRADA' => 'Terminada',
                default => $this->estatus,
            };
    }

    public function getTotalMetrosProducidosAttribute(): float
    {
        return (float) $this->detalles->sum('metros_producidos');
    }

    public function getTotalPiezasProducidasAttribute(): int
    {
        return (int) $this->detalles->sum('piezas_producidas');
    }
}
