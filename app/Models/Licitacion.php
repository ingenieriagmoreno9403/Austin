<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Licitacion extends Model
{
    use HasFactory;

    protected $table = 'tbllicitacion_enc';
    protected $fillable = [
        'id',
        'nombre',
        'folio',
        'fecha_creacion',
        'fecha_limite',
        'descripcion_detalle',
        'condiciones',
        'proveedor_adjudicado_id',
        'estado',
        'id_servicio',
        'tipo_param_id',
        'requisicion_id',
    ];

    public $timestamps = false;

    /** En curso / borrador (listado operativo). */
    public const ESTADOS_ACTIVAS = [
        'borrador',
        'activa',
        'en_proceso',
        'cotizando',
        'abierta',
    ];

    /** Cerradas: adjudicadas o cerradas → historial. */
    public const ESTADOS_HISTORIAL = [
        'adjudicada',
        'cerrada',
    ];

    public function detalles()
    {
        return $this->hasMany(Licitacion_det::class);
    }

    public function proveedores()
    {
        return $this->belongsToMany(Proveedor::class, 'tbllicitacion_proveedor');
    }

    public function proveedor_adjudicado()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_adjudicado_id');
    }

    public function adjudicaciones()
    {
        return $this->hasMany(LicitacionAdjudicacion::class);
    }

    public function tipoParam()
    {
        return $this->belongsTo(LicParamTipo::class, 'tipo_param_id');
    }

    public function parametrosEval()
    {
        return $this->hasMany(LicParametro::class, 'licitacion_id')->orderBy('orden');
    }

    public function paramScores()
    {
        return $this->hasMany(LicParamScore::class, 'licitacion_id');
    }

    public function requisicion()
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }
}
