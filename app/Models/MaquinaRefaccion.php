<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaquinaRefaccion extends Model
{
    use HasFactory;

    protected $table = 'tbl_maquina_refacciones';

    protected $fillable = [
        'id_maquina',
        'id_producto',
    ];
}
