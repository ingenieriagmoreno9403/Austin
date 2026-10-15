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

class PvBudgetExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents, WithTitle, WithStrictNullComparison
{
    /** @var array<int, array<int, mixed>> */
    protected $rows;

    /** @var string */
    protected $ciclo;

    public function __construct(array $rows, string $ciclo = '')
    {
        $this->rows = $rows;
        $this->ciclo = strtoupper(trim($ciclo));
    }

    public function headings(): array
    {
        return [
            'COMPANY',
            'ITEMCODE',
            'ITEMNAME',
            'CARDCODE',
            'CARDNAME',
            'MONTH',
            'YEAR',
            'QTY',
            'QTY PREVIOUS',
            'ORIGEN',
            'IMPORTEUSD',
            'IMPORTEMXN',
            'TC',
            'PRICEUSD',
            'PRICEMXN',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return $this->ciclo !== '' ? ('Budget '.$this->ciclo) : 'Budget';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(2, (int) $sheet->getHighestRow());
                $sheet->freezePane('A2');
                $sheet->getStyle('A1:O1')->getFont()->setBold(true);
                $sheet->getStyle('A1:O1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('111827');
                $sheet->getStyle('A1:O1')->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A1:O1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                // QTY + QTY ANTERIOR
                $sheet->getStyle('H2:I'.$lastRow)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                // Importes
                $sheet->getStyle('K2:L'.$lastRow)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                // TC + PRICEUSD + PRICEMXN
                $sheet->getStyle('M2:O'.$lastRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.0000');
            },
        ];
    }
}
