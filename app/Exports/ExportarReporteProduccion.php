<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExportarReporteProduccion implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    public function __construct(
        protected string $tituloHoja,
        protected array $encabezados,
        protected array $claves,
        protected Collection $filas
    ) {
    }

    public function collection(): Collection
    {
        return $this->filas;
    }

    public function title(): string
    {
        return \Illuminate\Support\Str::limit($this->tituloHoja, 31, '');
    }

    public function headings(): array
    {
        return $this->encabezados;
    }

    public function map($row): array
    {
        $r = is_array($row) ? $row : (array) $row;
        $out = [];
        foreach ($this->claves as $clave) {
            $out[] = $r[$clave] ?? '';
        }

        return $out;
    }
}
