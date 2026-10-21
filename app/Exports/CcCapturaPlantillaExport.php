<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CcCapturaPlantillaExport extends DefaultValueBinder implements FromArray, WithHeadings, ShouldAutoSize, WithEvents, WithTitle, WithStrictNullComparison, WithCustomValueBinder
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

    /**
     * Empresa, centro y cuenta se guardan como texto.
     * Si Excel los toma como número, 34.100 queda en 34.1 y la importación no encuentra el centro.
     */
    public function bindValue(Cell $cell, $value)
    {
        if (in_array($cell->getColumn(), ['A', 'B', 'C', 'D', 'E'], true) && $value !== null && ! is_int($value) && ! is_float($value)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastCol = $event->sheet->getDelegate()->getHighestColumn();
                $lastRow = max(2, (int) $event->sheet->getDelegate()->getHighestRow());
                $sheet = $event->sheet->getDelegate();
                $event->sheet->freezePane('A2');
                $sheet->getStyle('A1:'.$lastCol.'1')->getFont()->setBold(true);
                $sheet->getStyle('A1:'.$lastCol.'1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('111827');
                $sheet->getStyle('A1:'.$lastCol.'1')->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A2:E'.$lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
                $textCols = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4];
                foreach ($this->rows as $i => $row) {
                    $excelRow = $i + 2;
                    foreach ($textCols as $col => $idx) {
                        $text = isset($row[$idx]) && $row[$idx] !== null ? (string) $row[$idx] : '';
                        $sheet->setCellValueExplicit($col.$excelRow, $text, DataType::TYPE_STRING);
                    }
                    if (! empty($this->captured[$i])) {
                        continue;
                    }
                    $sheet->getStyle('A'.$excelRow.':E'.$excelRow)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F4F4F5');
                    $sheet->getStyle('F'.$excelRow.':'.$lastCol.$excelRow)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFFBEB');
                }
            },
        ];
    }
}
