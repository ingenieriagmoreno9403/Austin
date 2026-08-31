<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIva extends Model
{
    protected $table = 'tbl_tipos_iva';

    protected $fillable = [
        'codigo',
        'nombre',
        'porcentaje',
        'descripcion',
        'estatus',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
    ];

    public function etiqueta(): string
    {
        return $this->nombre . ' (' . rtrim(rtrim(number_format((float) $this->porcentaje, 2, '.', ''), '0'), '.') . '%)';
    }
}
