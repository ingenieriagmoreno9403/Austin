<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Sin ShouldAutoSize: en archivos grandes (miles de filas) es muy lento y provoca 504.
 */
class PvBudgetExport implements FromArray, WithHeadings, WithEvents, WithTitle, WithStrictNullComparison
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
                $sheet->freezePane('A2');
                $sheet->getStyle('A1:O1')->getFont()->setBold(true);
                $sheet->getStyle('A1:O1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('111827');
                $sheet->getStyle('A1:O1')->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A1:O1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Anchos fijos (evita auto-size / formatos masivos que provocan 504).
                $widths = [
                    'A' => 12, 'B' => 16, 'C' => 36, 'D' => 12, 'E' => 28,
                    'F' => 8, 'G' => 8, 'H' => 12, 'I' => 14, 'J' => 10,
                    'K' => 14, 'L' => 14, 'M' => 10, 'N' => 12, 'O' => 12,
                ];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }
            },
        ];
    }
}
