<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clientes extends Model
{
    use HasFactory;
    public $table='tblclientes';
    
    protected $fillable = [
        'nombre', 'alias', 'estado', 'tipo', 'razon_social', 'rfc', 'telefono',
        'correo_electronico', 'id_ciudad', 'colonia', 'calle', 'numero_int',
        'numero_ext', 'cp', 'moneda_id', 'condicion_pago_id', 'created_by', 'updated_by'
    ];

    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'moneda_id');
    }

    public function condicionPago()
    {
        return $this->belongsTo(CondicionPago::class, 'condicion_pago_id');
    }

    public function ciudad()
    {
        return $this->belongsTo(Ciudades::class, 'id_ciudad');
    }

    public function personasAtencion()
    {
        return $this->hasMany(ClientesAtencion::class, 'id_cliente');
    }

    /** Dirección de entrega formateada (calle, colonia, CP, ciudad). */
    public function direccionCompleta(): string
    {
        $calleNum = trim(implode(' ', array_filter([
            $this->calle ?? null,
            $this->numero_ext ? ('#' . $this->numero_ext) : null,
            $this->numero_int ? ('Int. ' . $this->numero_int) : null,
        ])));

        $partes = array_filter([
            $calleNum !== '' ? $calleNum : null,
            $this->colonia ?? null,
            $this->cp ? ('CP ' . $this->cp) : null,
            optional($this->ciudad)->nombre ?? null,
        ]);

        return trim(implode(', ', $partes));
    }

    /**
     * Nombre de la persona de atención: primera de catálogo o el campo nombre del cliente.
     */
    public function nombrePersonaAtencion(): string
    {
        $persona = $this->relationLoaded('personasAtencion')
            ? $this->personasAtencion->first()
            : $this->personasAtencion()->orderBy('id')->first();

        if ($persona) {
            return strtoupper(trim((string) $persona->nombre_completo));
        }

        return strtoupper(trim((string) ($this->nombre ?? '')));
    }
}
