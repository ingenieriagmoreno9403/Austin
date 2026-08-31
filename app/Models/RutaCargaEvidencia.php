<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RutaCargaEvidencia extends Model
{
    protected $table = 'tbl_ruta_carga_evidencias';

    protected $fillable = [
        'ruta_id',
        'archivo_path',
        'archivo_nombre',
        'subido_por',
    ];

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(RutaCarga::class, 'ruta_id');
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
