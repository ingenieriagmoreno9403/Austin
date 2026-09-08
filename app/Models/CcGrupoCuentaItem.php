<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CcGrupoCuentaItem extends Model
{
    public $table = 'tbl_cc_grupo_cuentas';

    protected $fillable = [
        'grupo_id',
        'cuenta_codigo',
        'cuenta_nombre',
    ];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(CcGrupoCuenta::class, 'grupo_id');
    }
}
