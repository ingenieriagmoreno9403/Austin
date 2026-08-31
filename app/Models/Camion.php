<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Camion extends Model
{
    protected $table = 'tbl_camiones';

    protected $fillable = [
        'placas',
        'nombre',
        'tipo',
        'capacidad_ton',
        'capacidad_metros',
        'perm_sct',
        'num_permiso_sct',
        'config_vehicular',
        'anio_modelo',
        'asegura_resp_civil',
        'poliza_resp_civil',
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'capacidad_ton' => 'decimal:3',
        'capacidad_metros' => 'decimal:3',
        'activo' => 'boolean',
    ];

    public function rutas(): HasMany
    {
        return $this->hasMany(RutaCarga::class, 'camion_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function getEtiquetaAttribute(): string
    {
        $base = $this->placas ?: ('Camion #' . $this->id);
        if ($this->nombre) {
            return $base . ' - ' . $this->nombre;
        }

        return $base;
    }

    public function getEtiquetaConCapacidadAttribute(): string
    {
        $parts = [];
        if ($this->capacidad_ton !== null && $this->capacidad_ton !== '') {
            $parts[] = number_format((float) $this->capacidad_ton, 2, '.', '') . ' t';
        }
        if ($this->capacidad_metros !== null && $this->capacidad_metros !== '') {
            $parts[] = number_format((float) $this->capacidad_metros, 2, '.', '') . ' m3';
        }

        if (empty($parts)) {
            return $this->etiqueta;
        }

        return $this->etiqueta . ' (' . implode(' / ', $parts) . ')';
    }
}
