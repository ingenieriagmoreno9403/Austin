<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requisicion extends Model
{
    protected $table = 'tbl_requisiciones';

    public const ESTADOS = [
        'borrador' => 'Borrador',
        'pendiente_autorizacion' => 'Pendiente de autorización',
        'devuelta' => 'Devuelta para corrección',
        'reenviada' => 'Reenviada',
        'rechazada' => 'Rechazada',
        'autorizada' => 'Autorizada',
        'en_proceso_compra' => 'En proceso de compra',
        'atendida_parcial' => 'Atendida parcialmente',
        'atendida' => 'Atendida',
        'cancelada' => 'Cancelada',
    ];

    public const PRIORIDADES = [
        'baja' => 'Baja',
        'normal' => 'Normal',
        'alta' => 'Alta',
        'urgente' => 'Urgente',
    ];

    protected $fillable = [
        'folio',
        'solicitante_id',
        'departamento',
        'centro_costo',
        'almacen_destino',
        'prioridad',
        'fecha_solicitud',
        'fecha_requerida',
        'justificacion',
        'estado',
        'ruta_id',
        'nivel_actual_orden',
        'responsable_actual_id',
        'enviado_at',
        'autorizado_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_requerida' => 'date',
        'enviado_at' => 'datetime',
        'autorizado_at' => 'datetime',
        'nivel_actual_orden' => 'integer',
    ];

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function responsableActual(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_actual_id');
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(ReqRuta::class, 'ruta_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(RequisicionDetalle::class, 'requisicion_id')->orderBy('orden');
    }

    public function aprobaciones(): HasMany
    {
        return $this->hasMany(RequisicionAprobacion::class, 'requisicion_id')->orderBy('orden');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(RequisicionHistorial::class, 'requisicion_id')->orderByDesc('id');
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function prioridadLabel(): string
    {
        return self::PRIORIDADES[$this->prioridad] ?? $this->prioridad;
    }

    public function esEditablePor(int $userId): bool
    {
        if ((int) $this->solicitante_id !== $userId) {
            return false;
        }

        return in_array($this->estado, ['borrador', 'devuelta'], true);
    }
}
