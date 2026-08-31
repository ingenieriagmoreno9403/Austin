<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MantenimientoFoto extends Model
{
    use HasFactory;

    protected $table = 'tbl_mantenimientos_fotos';

    public $timestamps = false;

    protected $fillable = [
        'mantenimiento_id',
        'nombre_archivo',
        'ruta',
        'descripcion',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'mantenimiento_id');
    }
}
