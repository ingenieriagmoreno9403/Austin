<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AguinaldoNotimbrado extends Model
{
    use HasFactory;
    public $table='tblaguinaldo_notimbrado';
    
    protected $fillable = [
        'mensaje_error',
        'idaguinaldo_enc',
        'id_aguinaldo_det',
        'fecha_inicio',
        'fecha_fin',
        'nombre_aguinaldo',
        'id_empleado',
        'created_by'
    ];
}

