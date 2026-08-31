<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientesAtencion extends Model
{
    use HasFactory;
    public $table='tblclientes_atencion';
    
    protected $fillable = [
        'id_cliente',
        'primer_nombre',
        'segundo_nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'correo',
        'created_at',
        'updated_at'
    ];

    /**
     * Obtener el nombre completo de la persona de atención
     */
    public function getNombreCompletoAttribute()
    {
        $nombre = $this->primer_nombre;
        if ($this->segundo_nombre) {
            $nombre .= ' ' . $this->segundo_nombre;
        }
        $nombre .= ' ' . $this->apellido_paterno;
        if ($this->apellido_materno) {
            $nombre .= ' ' . $this->apellido_materno;
        }
        return $nombre;
    }

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }

    public function servicios()
    {
        return $this->hasMany(ServiciosEnc::class, 'id_atencion');
    }
}
