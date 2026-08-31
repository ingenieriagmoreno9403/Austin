<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CotizacionBorradorSuministro extends Model
{
    use HasFactory;
    protected $table = 'tbl_cotizacion_borrador_suministros';
    
    protected $fillable = [
        'id_serv_enc',
        'cotizacion',
        'borrador',
        'created_by',
        'updated_at',
    ];
}
