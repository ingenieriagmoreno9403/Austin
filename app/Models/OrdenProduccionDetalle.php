<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenProduccionDetalle extends Model
{
    protected $table = 'tbl_ordenes_detalle';

    public $timestamps = false;

    protected $fillable = [
        'orden_id',
        'producto_id',
        'tipo_linea',
        'diametro',
        'rd',
        'psi',
        'espesor',
        'kg_metro',
        'metros_producidos',
        'kg_teorico',
        'kg_real',
        'kg_merma',
        'porcentaje_merma',
        'piezas_producidas',
        'largo_tramo',
        'ubicacion_destino_id',
        'observaciones',
        'created_at',
    ];

    protected $casts = [
        'psi' => 'decimal:2',
        'espesor' => 'decimal:3',
        'kg_metro' => 'decimal:4',
        'metros_producidos' => 'decimal:3',
        'kg_teorico' => 'decimal:3',
        'kg_real' => 'decimal:3',
        'kg_merma' => 'decimal:3',
        'porcentaje_merma' => 'decimal:3',
        'largo_tramo' => 'decimal:3',
        'created_at' => 'datetime',
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenProduccion::class, 'orden_id');
    }

    public function producto()
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }

    public function salidas()
    {
        return $this->hasMany(OrdenProduccionDetalleSalida::class, 'orden_detalle_id');
    }

    public function ubicacionDestino()
    {
        return $this->belongsTo(Ubicaciones::class, 'ubicacion_destino_id');
    }

    public function esFabricar(): bool
    {
        if ($this->tipo_linea) {
            return $this->tipo_linea === 'FABRICAR';
        }

        if (!$this->producto_id) {
            return false;
        }

        if (ProductoTuboEspecificacion::where('producto_id', $this->producto_id)->where('estatus', 'ACTIVO')->exists()) {
            return true;
        }

        if (ProductoFlangeEspecificacion::where('producto_id', $this->producto_id)->where('estatus', 'ACTIVO')->exists()) {
            return true;
        }

        return ProductoConexionEspecificacion::where('producto_id', $this->producto_id)
            ->where('estatus', 'ACTIVO')
            ->exists();
    }

    public function esExistencia(): bool
    {
        return !$this->esFabricar();
    }

    public function recalcularTotalesDesdeSalidas(): void
    {
        $salidas = $this->salidas;

        $metros = (float) $salidas->sum('metros');
        $piezas = (int) $salidas->sum(function ($s) {
            return $s->piezas_buenas !== null ? (int) $s->piezas_buenas : (int) ($s->piezas ?? 0);
        });
        $kgReal = $salidas->whereNotNull('kg_real')->sum('kg_real');
        $kgTeorico = (float) $salidas->sum('kg_teorico');
        $kgMerma = (float) $salidas->sum('kg_merma');
        $porcentajeMerma = $kgTeorico > 0 ? round(($kgMerma / $kgTeorico) * 100, 3) : null;

        $this->update([
            'metros_producidos' => $metros,
            'piezas_producidas' => $piezas,
            'kg_real' => $kgReal > 0 ? round($kgReal, 3) : null,
            'kg_teorico' => $kgTeorico > 0 ? round($kgTeorico, 3) : null,
            'kg_merma' => $kgMerma > 0 ? round($kgMerma, 3) : null,
            'porcentaje_merma' => $porcentajeMerma,
        ]);
    }
}
