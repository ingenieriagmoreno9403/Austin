<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspeccionFoto extends Model
{
    use HasFactory;

    protected $table = 'tbl_inspecciones_fotos';

    public $timestamps = false;

    protected $fillable = [
        'inspeccion_id',
        'nombre_archivo',
        'ruta',
        'descripcion',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function inspeccion()
    {
        return $this->belongsTo(InspeccionMaquina::class, 'inspeccion_id');
    }
}
