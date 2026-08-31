<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Licitacion_det extends Model
{
    use HasFactory;

    
    protected $table = 'tbllicitacion_det';
    protected $fillable=['id','producto_id','cantidad','licitacion_id','unidad_medida','observaciones','costo'];
    public $timestamps=false;

    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class);
    } 
    
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
