<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chofer extends Model
{
    protected $table = 'tbl_choferes';

    public const TIPO_INTERNO = 'INTERNO';
    public const TIPO_EXTERNO = 'EXTERNO';

    public static array $tipos = [
        self::TIPO_INTERNO => 'Interno',
        self::TIPO_EXTERNO => 'Externo',
    ];

    protected $fillable = [
        'nombre',
        'tipo',
        'telefono',
        'licencia',
        'rfc',
        'empresa_externa',
        'empleado_id',
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleados::class, 'empleado_id');
    }

    public function rutas(): HasMany
    {
        return $this->hasMany(RutaCarga::class, 'chofer_id');
    }

    public function getEtiquetaAttribute(): string
    {
        $tipo = self::$tipos[$this->tipo] ?? $this->tipo;
        $extra = $this->tipo === self::TIPO_EXTERNO && $this->empresa_externa
            ? ' · ' . $this->empresa_externa
            : '';

        return trim($this->nombre . ' (' . $tipo . ')' . $extra);
    }

    public function getTipoTextoAttribute(): string
    {
        return self::$tipos[$this->tipo] ?? (string) $this->tipo;
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
