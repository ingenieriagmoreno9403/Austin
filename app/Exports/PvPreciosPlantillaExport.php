<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PvPreciosPlantillaExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents, WithTitle, WithStrictNullComparison
{
    /** @var array<int, array<int, mixed>> */
    protected $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return ['Empresa', 'CardCode', 'ItemCode', 'Mes', 'Precio'];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Precios';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(2, (int) $sheet->getHighestRow());
                $sheet->freezePane('A2');
                $sheet->getStyle('A1:E1')->getFont()->setBold(true);
                $sheet->getStyle('A1:E1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('111827');
                $sheet->getStyle('A1:E1')->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('E2:E'.$lastRow)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            },
        ];
    }
}
