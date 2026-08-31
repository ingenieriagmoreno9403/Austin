<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Licitacion extends Model
{
    use HasFactory;

    protected $table = 'tbllicitacion_enc';
    protected $fillable=['id','nombre','folio','fecha_creacion','fecha_limite','descripcion_detalle','proveedor_adjudicado_id','estado','id_servicio'];
    public $timestamps=false;

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
}
