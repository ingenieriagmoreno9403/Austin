<?php

namespace App\Models;

use App\Models\EntradaInventario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EntradaInventarioDet extends Model
{
    use HasFactory;

    protected $table = 'tblentradas_inventario_det'; // <-- Aquí también apuntamos bien

    protected $fillable = [
        'entrada_inventario_id',
        'producto_id',
        'cantidad_pedida',
        'cantidad_recibida',
        'comentario',
    ];

    public function entrada()
    {
        return $this->belongsTo(EntradaInventario::class, 'entrada_inventario_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
