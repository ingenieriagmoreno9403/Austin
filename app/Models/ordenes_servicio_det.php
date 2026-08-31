<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ordenes_servicio_det extends Model
{
    use HasFactory;

    public $table = 'tblordenes_servicio_det';

    protected $fillable = [
        'id_orden_enc',
        'no_s',
        'servicio',
        'descripcion',
        't_tab',
        't_real',
        'created_by',
        'updated_by',
    ];
}

