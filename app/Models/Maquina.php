<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maquina extends Model
{
    use HasFactory;

    protected $table = 'tbl_maquinas';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'ubicacion',
        'id_almacen',
        'id_ubicacion',
        'capacidad_pr_hora',
        'potencia_kw',
        'horas_turno',
        'turnos_dia',
        'operador_responsable_id',
        'estatus',
        'para_reproceso',
        'ruta_manual',
        'otrosconceptos1',
        'otrosconceptos2',
        'otrosconceptos3',
    ];

    protected $casts = [
        'capacidad_pr_hora' => 'decimal:3',
        'potencia_kw' => 'decimal:3',
        'horas_turno' => 'decimal:2',
        'para_reproceso' => 'boolean',
    ];

    public function scopeActivas($query)
    {
        return $query->where('estatus', 'A');
    }

    /** Trituradoras / peletizadoras usadas en el módulo de reproceso. */
    public function scopeParaReproceso($query)
    {
        return $query->where('para_reproceso', true)->where('estatus', 'A');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacenes::class, 'id_almacen');
    }

    public function ubicacionAlmacen()
    {
        return $this->belongsTo(Ubicaciones::class, 'id_ubicacion');
    }

    public function refacciones()
    {
        return $this->belongsToMany(Productos::class, 'tbl_maquina_refacciones', 'id_maquina', 'id_producto')
            ->withTimestamps();
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'maquina_id');
    }

    public function inspecciones()
    {
        return $this->hasMany(InspeccionMaquina::class, 'maquina_id');
    }

    public function incidencias()
    {
        return $this->hasMany(IncidenciaMaquina::class, 'maquina_id');
    }

    public function programacionesMantenimiento()
    {
        return $this->hasMany(ProgramacionMantenimiento::class, 'maquina_id');
    }

    public function operadorResponsable()
    {
        return $this->belongsTo(Empleados::class, 'operador_responsable_id');
    }
}
