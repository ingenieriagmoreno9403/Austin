<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Productos extends Model
{
    use HasFactory;
    public $table='tblproductos';

    public const TIPO_PROCESO_TUBO = 'TUBO';
    public const TIPO_PROCESO_FLANGE = 'FLANGE';
    public const TIPO_PROCESO_CONEXION = 'CONEXION';
    public const TIPO_PROCESO_OTRO = 'OTRO';

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

    public function especificacionesTubo()
    {
        return $this->hasMany(ProductoTuboEspecificacion::class, 'producto_id');
    }

    public function especificacionesFlange()
    {
        return $this->hasMany(ProductoFlangeEspecificacion::class, 'producto_id');
    }

    public function especificacionesConexion()
    {
        return $this->hasMany(ProductoConexionEspecificacion::class, 'producto_id');
    }

    public function especificacionFlangeActiva()
    {
        return $this->hasOne(ProductoFlangeEspecificacion::class, 'producto_id')
            ->where('estatus', 'ACTIVO')
            ->latest('id');
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidad_medida');
    }

    public function recetas()
    {
        return $this->hasMany(Receta::class, 'producto_id');
    }

    public function recetaActiva()
    {
        return $this->hasOne(Receta::class, 'producto_id')->where('estatus', 'ACTIVA')->latest('id');
    }
}
