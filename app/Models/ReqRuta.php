<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReqRuta extends Model
{
    protected $table = 'tbl_req_rutas';

    protected $fillable = [
        'nombre',
        'departamento',
        'tipo_requisicion',
        'activo',
        'es_default',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'es_default' => 'boolean',
    ];

    public function niveles(): HasMany
    {
        return $this->hasMany(ReqNivel::class, 'ruta_id')->orderBy('orden');
    }
}
