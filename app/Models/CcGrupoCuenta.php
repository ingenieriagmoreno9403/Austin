<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CcGrupoCuenta extends Model
{
    public $table = 'tbl_cc_grupos_cuenta';

    protected $fillable = [
        'empresa',
        'clave',
        'nombre',
        'created_by',
        'updated_by',
    ];

    public function cuentas(): HasMany
    {
        return $this->hasMany(CcGrupoCuentaItem::class, 'grupo_id');
    }
}
