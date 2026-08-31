<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    use HasFactory;

    protected $table = 'tbl_mantenimientos';

    public $timestamps = false;

    protected $fillable = [
        'folio',
        'maquina_id',
        'tipo',
        'fecha_reporte',
        'fecha_programada',
        'fecha_inicio',
        'fecha_fin',
        'responsable_id',
        'incidencia_id',
        'causa_falla_id',
        'descripcion',
        'horas_paro',
        'costo_mano_obra',
        'costo_refacciones',
        'costo_total',
        'resultado',
        'estatus',
        'created_at',
    ];

    protected $casts = [
        'fecha_reporte' => 'date',
        'fecha_programada' => 'date',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'horas_paro' => 'decimal:2',
        'costo_mano_obra' => 'decimal:2',
        'costo_refacciones' => 'decimal:2',
        'costo_total' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function responsable()
    {
        return $this->belongsTo(Empleados::class, 'responsable_id');
    }

    public function incidencia()
    {
        return $this->belongsTo(IncidenciaMaquina::class, 'incidencia_id');
    }

    public function causaFalla()
    {
        return $this->belongsTo(CausaFalla::class, 'causa_falla_id');
    }

    public function fotos()
    {
        return $this->hasMany(MantenimientoFoto::class, 'mantenimiento_id');
    }

    public function refacciones()
    {
        return $this->hasMany(RefaccionMaquina::class, 'mantenimiento_id');
    }

    public function getTipoTextoAttribute(): string
    {
        return match ($this->tipo) {
            'PREVENTIVO' => 'Preventivo',
            'CORRECTIVO' => 'Correctivo',
            'PREDICTIVO' => 'Predictivo',
            default => $this->tipo,
        };
    }

    public function getEstatusTextoAttribute(): string
    {
        return match ($this->estatus) {
            'PENDIENTE' => 'Pendiente',
            'EN_PROCESO' => 'En proceso',
            'FINALIZADO' => 'Finalizado',
            'CANCELADO' => 'Cancelado',
            default => $this->estatus,
        };
    }
}
