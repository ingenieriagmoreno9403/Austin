<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReqSuplente extends Model
{
    protected $table = 'tbl_req_suplentes';

    protected $fillable = [
        'nivel_id',
        'user_id',
        'vigencia_desde',
        'vigencia_hasta',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'vigencia_desde' => 'date',
        'vigencia_hasta' => 'date',
    ];

    public function nivel(): BelongsTo
    {
        return $this->belongsTo(ReqNivel::class, 'nivel_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vigenteHoy(): bool
    {
        if (! $this->activo) {
            return false;
        }
        $hoy = now()->startOfDay();
        if ($this->vigencia_desde && $hoy->lt($this->vigencia_desde->copy()->startOfDay())) {
            return false;
        }
        if ($this->vigencia_hasta && $hoy->gt($this->vigencia_hasta->copy()->startOfDay())) {
            return false;
        }

        return true;
    }
}
