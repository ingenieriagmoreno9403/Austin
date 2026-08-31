<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefaccionMaquina extends Model
{
    use HasFactory;

    protected $table = 'tbl_refacciones_maquina';

    public $timestamps = false;

    protected $fillable = [
        'mantenimiento_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
        'costo_total',
        'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'costo_unitario' => 'decimal:2',
        'costo_total' => 'decimal:2',
    ];

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'mantenimiento_id');
    }

    public function producto()
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }
}
