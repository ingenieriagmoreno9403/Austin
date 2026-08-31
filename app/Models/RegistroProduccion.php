<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroProduccion extends Model
{
    protected $table = 'tbl_registro_produccion';

    protected $fillable = [
        'orden_id',
        'hora_inicio',
        'hora_fin',
        't_programado_h',
        't_arranque_h',
        't_paros_h',
        't_operando_h',
        'mat_inicial_kg',
        'mat_reciclado_kg',
        'aditivos_kg',
        'mat_sobrante_kg',
        'mat_utilizado_kg',
        'prod_bueno_kg',
        'prod_defectuoso_kg',
        'total_producido_kg',
        'metros',
        'piezas_buenas',
        'piezas_malas',
        'porcentaje_merma',
        'rendimiento_pct',
        'prod_hora_kg',
        'prod_hora_piezas',
        'disponibilidad_pct',
        'rendimiento_oee_pct',
        'calidad_pct',
        'oee_pct',
        'costo_pead_kg',
        'costo_material',
        'costo_unit_m',
        'costo_unit_pieza',
        'kwh',
        'kwh_kg',
        'observaciones',
        'updated_by',
    ];

    protected $casts = [
        'hora_inicio' => 'datetime',
        'hora_fin' => 'datetime',
        't_programado_h' => 'decimal:3',
        't_arranque_h' => 'decimal:3',
        't_paros_h' => 'decimal:3',
        't_operando_h' => 'decimal:3',
        'mat_inicial_kg' => 'decimal:3',
        'mat_reciclado_kg' => 'decimal:3',
        'aditivos_kg' => 'decimal:3',
        'mat_sobrante_kg' => 'decimal:3',
        'mat_utilizado_kg' => 'decimal:3',
        'prod_bueno_kg' => 'decimal:3',
        'prod_defectuoso_kg' => 'decimal:3',
        'total_producido_kg' => 'decimal:3',
        'metros' => 'decimal:3',
        'piezas_buenas' => 'integer',
        'piezas_malas' => 'integer',
        'porcentaje_merma' => 'decimal:3',
        'rendimiento_pct' => 'decimal:3',
        'prod_hora_kg' => 'decimal:3',
        'prod_hora_piezas' => 'decimal:3',
        'disponibilidad_pct' => 'decimal:3',
        'rendimiento_oee_pct' => 'decimal:3',
        'calidad_pct' => 'decimal:3',
        'oee_pct' => 'decimal:3',
        'costo_pead_kg' => 'decimal:4',
        'costo_material' => 'decimal:2',
        'costo_unit_m' => 'decimal:4',
        'costo_unit_pieza' => 'decimal:4',
        'kwh' => 'decimal:3',
        'kwh_kg' => 'decimal:4',
    ];

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenProduccion::class, 'orden_id');
    }
}
