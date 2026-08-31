<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use HasFactory;

    protected $table = 'tbllicitacion_enc';
    protected $fillable=['id','nombre','fecha_creacion','fecha_limite','descripcion_detalle'];
    public $timestamps=false;
}
