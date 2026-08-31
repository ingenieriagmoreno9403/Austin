<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receta extends Model
{
    protected $table = 'tbl_recetas';

    public $timestamps = false;

    protected $fillable = [
        'producto_id',
        'version',
        'estatus',
        'observaciones',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(RecetaDetalle::class, 'receta_id');
    }

    public static function activaParaProducto(int $productoId): ?self
    {
        return static::with(['detalles.productoMp', 'detalles.unidad'])
            ->where('producto_id', $productoId)
            ->where('estatus', 'ACTIVA')
            ->orderByDesc('id')
            ->first();
    }
}
