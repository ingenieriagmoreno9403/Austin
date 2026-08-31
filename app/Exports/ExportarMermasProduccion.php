<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExportarMermasProduccion implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    protected string $seccion;
    protected Collection $filas;
    protected string $fechaInicio;
    protected string $fechaFin;

    public function __construct(string $seccion, Collection $filas, string $fechaInicio, string $fechaFin)
    {
        $this->seccion = $seccion;
        $this->filas = $filas;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    public function collection(): Collection
    {
        return $this->filas;
    }

    public function title(): string
    {
        return match ($this->seccion) {
            'resina' => 'Mermas resina',
            'materiales' => 'Mermas materiales',
            default => 'Mermas tubo',
        };
    }

    public function headings(): array
    {
        return match ($this->seccion) {
            'resina' => [
                'Fecha', 'Folio reproceso', 'OP', 'Origen', 'Estatus', 'Producto origen',
                'Resina', 'Metros', 'Kg origen', 'Kg triturado', 'Kg recuperada',
                'Kg pérdida proceso', 'Rendimiento %', 'Máquina',
            ],
            'materiales' => [
                'Fecha', 'OP', 'Pedido', 'Proceso', 'Producto PT', 'SKU PT',
                'Materia prima', 'SKU MP', 'Unidad', 'Cant. pedida', 'Cant. producida',
                'Tomado', 'Teórico x producido', 'Exceso vs producido', 'Kg merma proceso',
                'Merma estimada', 'Máquina',
            ],
            default => [
                'Fecha', 'OP', 'Pedido', 'Proceso', 'Producto', 'SKU', 'Diámetro', 'RD',
                'Kg/m', 'Máquina', 'Metros esperados', 'Metros reales', 'Metros faltantes',
                'Pzas buenas', 'Pzas malas', 'Kg teórico', 'Kg real', 'Kg merma',
                'Kg retrabajo', '% merma',
            ],
        };
    }

    public function map($row): array
    {
        $r = is_array($row) ? $row : (array) $row;

        return match ($this->seccion) {
            'resina' => [
                $r['fecha'] ?? '',
                $r['folio_reproceso'] ?? '',
                $r['folio_op'] ?? '',
                $r['origen'] ?? '',
                $r['estatus'] ?? '',
                $r['producto_origen'] ?? '',
                $r['producto_resina'] ?? '',
                $r['metros'] ?? 0,
                $r['kg_pesado'] ?? 0,
                $r['kg_triturado'] ?? 0,
                $r['kg_recuperada'] ?? 0,
                $r['kg_perdida_proceso'] ?? '',
                $r['rendimiento_pct'] ?? '',
                $r['maquina'] ?? '',
            ],
            'materiales' => [
                $r['fecha'] ?? '',
                $r['folio_op'] ?? '',
                $r['folio_pedido'] ?? '',
                $r['tipo_proceso'] ?? '',
                $r['producto_pt'] ?? '',
                $r['sku_pt'] ?? '',
                $r['materia_prima'] ?? '',
                $r['sku_mp'] ?? '',
                $r['unidad'] ?? '',
                $r['cantidad_pedida'] ?? 0,
                $r['cantidad_producida'] ?? 0,
                $r['tomado'] ?? 0,
                $r['teorico_producido'] ?? 0,
                $r['exceso_vs_producido'] ?? 0,
                $r['kg_merma_proceso'] ?? 0,
                $r['merma_estimada'] ?? 0,
                $r['maquina'] ?? '',
            ],
            default => [
                $r['fecha'] ?? '',
                $r['folio_op'] ?? '',
                $r['folio_pedido'] ?? '',
                $r['tipo_proceso'] ?? '',
                $r['producto'] ?? '',
                $r['sku'] ?? '',
                $r['diametro'] ?? '',
                $r['rd'] ?? '',
                $r['kg_metro'] ?? 0,
                $r['maquina'] ?? '',
                $r['metros_esperados'] ?? 0,
                $r['metros_reales'] ?? 0,
                $r['metros_faltantes'] ?? 0,
                $r['piezas_buenas'] ?? 0,
                $r['piezas_malas'] ?? 0,
                $r['kg_teorico'] ?? 0,
                $r['kg_real'] ?? 0,
                $r['kg_merma'] ?? 0,
                $r['kg_retrabajo'] ?? 0,
                $r['porcentaje_merma'] ?? 0,
            ],
        };
    }
}
