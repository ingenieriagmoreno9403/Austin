<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuenta extends Model
{
    use HasFactory;
    
    protected $table = 'tblcuentas';
    
    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'saldo_actual',
        'fecha_creacion'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'saldo_actual' => 'decimal:2',
        'fecha_creacion' => 'datetime'
    ];

    /**
     * Obtener los ingresos asociados a esta cuenta
     */
    public function ingresos()
    {
        return $this->hasMany(Ingreso::class, 'cuenta_id');
    }

    /**
     * Obtener los egresos asociados a esta cuenta
     */
    public function egresos()
    {
        return $this->hasMany(Egreso::class, 'cuenta_id');
    }

    /**
     * Formatear el saldo para mostrar
     */
    public function getSaldoFormateadoAttribute()
    {
        return '$' . number_format($this->saldo_actual, 2);
    }

    /**
     * Scope para filtrar cuentas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('saldo_actual', '>=', 0);
    }
}
