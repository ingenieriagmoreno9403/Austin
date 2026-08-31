<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CostosLicitacionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $costos;
    protected $licitacion;

    public function __construct($costos, $licitacion)
    {
        $this->costos = $costos;
        $this->licitacion = $licitacion;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->costos;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Proveedor',
            'Producto',
            'Marca',
            'Costo',
        ];
    }

    public function map($costo): array
    {
        return [
            $costo->producto_id,
            $costo->proveedor,
            $costo->producto,
            $costo->marca,
            $costo->costo,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la primera fila (encabezados)
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ],
            
            // Estilo para todas las celdas
            'A1:E1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
            
            // Estilo para la columna de costos
            'E' => [
                'numberFormat' => [
                    'formatCode' => '$#,##0.00',
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Costos Licitación #' . $this->licitacion->id;
    }
}
