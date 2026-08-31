<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProveedoresAtencion extends Model
{
    use HasFactory;
    protected $table = 'tblproveedores_atencion';
    
    protected $fillable = [
        'id',
        'id_proveedor',
        'primer_nombre',
        'segundo_nombre',
        'apellido_paterno',
        'apellido_materno',
        'puesto'
    ];
    
    public $timestamps = false;
    
    // Relación con el proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }
    
    // Accessor para el nombre completo
    public function getNombreCompletoAttribute()
    {
        $nombre = $this->primer_nombre;
        if ($this->segundo_nombre) {
            $nombre .= ' ' . $this->segundo_nombre;
        }
        if ($this->apellido_paterno) {
            $nombre .= ' ' . $this->apellido_paterno;
        }
        if ($this->apellido_materno) {
            $nombre .= ' ' . $this->apellido_materno;
        }
        return $nombre;
    }
    
    // Scope para obtener solo los activos (asumiendo que no hay campo activo)
    public function scopeActivos($query)
    {
        return $query; // Por ahora retornamos todos
    }
}
