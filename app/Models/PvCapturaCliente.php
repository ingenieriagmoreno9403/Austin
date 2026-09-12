<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvCapturaCliente extends Model
{
    public $table = 'tbl_pv_captura_clientes';

    protected $fillable = [
        'ciclo_codigo',
        'empresa',
        'cliente_codigo',
        'estado',
        'updated_by',
    ];

    public function getCentroCodigoAttribute()
    {
        return $this->attributes['cliente_codigo'] ?? null;
    }

    public function setCentroCodigoAttribute($value)
    {
        $this->attributes['cliente_codigo'] = $value;
    }
}
