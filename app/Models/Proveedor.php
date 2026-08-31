<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;
    protected $table = 'tblprovedores';
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre', 
        'direccion', 
        'telefono', 
        'email', 
        'rfc', 
        'estado', 
        'modo_pago', 
        'dias_credito'
    ];

    public function licitaciones()
    {
        return $this->belongsToMany(Licitacion::class, 'tbllicitacion_proveedor');
    }
    
    // Relación con las personas de atención
    public function personasAtencion()
    {
        return $this->hasMany(ProveedoresAtencion::class, 'id_proveedor')->activos();
    }

}
