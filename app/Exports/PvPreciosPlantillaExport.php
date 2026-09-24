<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PvPreciosPlantillaExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents, WithTitle, WithStrictNullComparison
{
    /** @var array<int, array<int, mixed>> */
    protected $rows;

    /** @var int Año de proyección (solo informativo en título). */
    protected $anio;

    public function __construct(array $rows, int $anio = 0)
    {
        $this->rows = $rows;
        $this->anio = $anio;
    }

    public function headings(): array
    {
        return [
            'Empresa',
            'CardCode',
            'Cliente',
            'ItemCode',
            'Producto',
            'Moneda',
            'PrecioGlobal',
            'Ene',
            'Feb',
            'Mar',
            'Abr',
            'May',
            'Jun',
            'Jul',
            'Ago',
            'Sep',
            'Oct',
            'Nov',
            'Dic',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return $this->anio > 0 ? ('Precios '.$this->anio) : 'Precios';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(2, (int) $sheet->getHighestRow());
                $sheet->freezePane('A2');
                $sheet->getStyle('A1:S1')->getFont()->setBold(true);
                $sheet->getStyle('A1:S1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('111827');
                $sheet->getStyle('A1:S1')->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A1:S1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                // PrecioGlobal + 12 meses
                $sheet->getStyle('G2:S'.$lastRow)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                // Hint en fila de instrucciones (columna oculta no; usamos comentario en G1 vía nota en hoja).
                $sheet->getComment('G1')->getText()->createTextRun(
                    'Precio global (mes=0). Si un mes está vacío al subir, no se toca. '.
                    'Si llenas Ene–Dic, se guardan esos meses. Si solo llenas PrecioGlobal, aplica a los 12.'
                );
            },
        ];
    }
}
