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

class CcCapturaPlantillaExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents, WithTitle, WithStrictNullComparison
{
    /** @var array<int, string> */
    protected $headings;

    /** @var array<int, array<int, mixed>> */
    protected $rows;

    /** @var array<int, bool> */
    protected $captured;

    public function __construct(array $headings, array $rows, array $captured = [])
    {
        $this->headings = $headings;
        $this->rows = $rows;
        $this->captured = $captured;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Captura';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastCol = $event->sheet->getDelegate()->getHighestColumn();
                $lastRow = max(2, (int) $event->sheet->getDelegate()->getHighestRow());
                $event->sheet->freezePane('A2');
                $event->sheet->getDelegate()->getStyle('A1:'.$lastCol.'1')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A1:'.$lastCol.'1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('111827');
                $event->sheet->getDelegate()->getStyle('A1:'.$lastCol.'1')->getFont()->getColor()->setRGB('FFFFFF');
                foreach ($this->rows as $i => $row) {
                    $excelRow = $i + 2;
                    if (! empty($this->captured[$i])) {
                        continue;
                    }
                    $event->sheet->getDelegate()->getStyle('A'.$excelRow.':E'.$excelRow)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F4F4F5');
                    $event->sheet->getDelegate()->getStyle('F'.$excelRow.':'.$lastCol.$excelRow)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFFBEB');
                }
            },
        ];
    }
}
