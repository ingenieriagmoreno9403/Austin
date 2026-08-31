<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;
    public $table='tblproductos';  
    
    
    public function unidad()
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidad_medida');
    }
}
