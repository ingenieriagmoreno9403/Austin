<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntradaInventario extends Model
{
    use HasFactory;

    protected $table = 'tblentradas_inventario';

    protected $fillable = [
        'orden_compra_id',
        'fecha_recepcion',
        'observaciones',
        'status',
        'id_almacen',
        'id_ubicacion',
    ];

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function detalles()
    {
        return $this->hasMany(EntradaInventarioDet::class, 'entrada_inventario_id');
    }
    
    public function almacen()
    {
        return $this->belongsTo(Almacenes::class, 'id_almacen');
    }
    
    public function ubicacion()
    {
        return $this->belongsTo(Ubicaciones::class, 'id_ubicacion');
    }
}
