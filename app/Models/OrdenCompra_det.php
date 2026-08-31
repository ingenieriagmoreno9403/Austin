<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenCompra_det extends Model
{
    use HasFactory;
    protected $table = 'tblordencompra_det';
    protected $fillable=['id','producto_id','cantidad','cantidad_recibida','orden_compra_id','unidad_medida','observaciones','costo'];
    public $timestamps=false;

    protected $attributes = [
        'cantidad_recibida' => 0,
    ];

    public function OrdenCompra()
    {
        return $this->belongsTo(OrdenCompra::class);
    } 
    
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
