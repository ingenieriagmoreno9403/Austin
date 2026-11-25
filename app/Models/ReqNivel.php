<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReqNivel extends Model
{
    protected $table = 'tbl_req_niveles';

    protected $fillable = [
        'ruta_id',
        'orden',
        'nombre_nivel',
        'responsable_user_id',
        'obligatorio',
    ];

    protected $casts = [
        'obligatorio' => 'boolean',
        'orden' => 'integer',
    ];

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(ReqRuta::class, 'ruta_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }

    public function suplentes(): HasMany
    {
        return $this->hasMany(ReqSuplente::class, 'nivel_id');
    }
}
