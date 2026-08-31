<?php

namespace App\Exports;

use App\Models\Nominas_pagosenc;
use App\Traits\DatosimpleTraits;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Throwable;

class RetencionesNominaExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    use DatosimpleTraits;
    use Exportable;

    protected int $id;

    protected array $meta = [];

    protected int $totalFilas = 0;

    protected int $filaEncabezados = 8;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->meta = $this->resolverMetaNomina($id);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $ultimaFila = max($this->totalFilas + $this->filaEncabezados, $this->filaEncabezados);
                $columnaFin = 'O';

                $sheet->mergeCells('B1:' . $columnaFin . '1');
                $sheet->mergeCells('B2:' . $columnaFin . '2');
                $sheet->mergeCells('B3:' . $columnaFin . '3');
                $sheet->mergeCells('B4:' . $columnaFin . '4');
                $sheet->mergeCells('B5:' . $columnaFin . '5');
                $sheet->mergeCells('B6:' . $columnaFin . '6');

                $sheet->getStyle('A1:A6')->getFont()->setBold(true);
                $sheet->getStyle('A' . $this->filaEncabezados . ':' . $columnaFin . $this->filaEncabezados)->getFont()->setBold(true);

                $sheet->getStyle('A1:' . $columnaFin . '6')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC'],
                    ],
                ]);

                $sheet->getStyle('A' . $this->filaEncabezados . ':' . $columnaFin . $this->filaEncabezados)
                    ->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '000000'],
                        ],
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        ],
                    ]);

                if ($this->totalFilas > 0) {
                    $filaTotales = $ultimaFila;
                    $sheet->getStyle('A' . $filaTotales . ':' . $columnaFin . $filaTotales)->getFont()->setBold(true);
                    $sheet->getStyle('A' . $filaTotales . ':' . $columnaFin . $filaTotales)
                        ->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'DCFCE7'],
                            ],
                        ]);

                    $sheet->getStyle('G' . ($this->filaEncabezados + 1) . ':' . $columnaFin . $filaTotales)
                        ->getNumberFormat()
                        ->setFormatCode('$#,##0.00');
                }

                $sheet->getStyle('A1:' . $columnaFin . $ultimaFila)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                $sheet->setAutoFilter('A' . $this->filaEncabezados . ':' . $columnaFin . $this->filaEncabezados);
            },
        ];
    }

    public function collection()
    {
        try {
            $empleados = $this->obtenerRetencionesNominaExport($this->id);

            if ($empleados->isEmpty()) {
                throw new \RuntimeException("La nómina {$this->id} no tiene empleados para generar el reporte de retenciones.");
            }

            $rows = collect();
            $totales = [
                'sueldo_bruto' => 0,
                'isr' => 0,
                'imss' => 0,
                'infonavit' => 0,
                'fonacot' => 0,
                'prestamo' => 0,
                'otras' => 0,
                'total_retenciones' => 0,
                'neto' => 0,
            ];

            foreach ($empleados as $empleado) {
                $sueldoBruto = $this->calcularSueldoBruto($empleado);
                $isr = (float) $empleado->pago_isr;
                $imss = (float) $empleado->pago_imss;
                $infonavit = (float) $empleado->pago_infonavit;
                $fonacot = (float) $empleado->fonacot;
                $prestamo = (float) $empleado->deudores_fiscal;
                $otras = (float) $empleado->otros;
                $totalRetenciones = $isr + $imss + $infonavit + $fonacot + $prestamo + $otras;
                $neto = (float) $empleado->total_apagar;

                $totales['sueldo_bruto'] += $sueldoBruto;
                $totales['isr'] += $isr;
                $totales['imss'] += $imss;
                $totales['infonavit'] += $infonavit;
                $totales['fonacot'] += $fonacot;
                $totales['prestamo'] += $prestamo;
                $totales['otras'] += $otras;
                $totales['total_retenciones'] += $totalRetenciones;
                $totales['neto'] += $neto;

                $rows->push([
                    'numero_empleado' => $empleado->id_empleado,
                    'nombre_completo' => $empleado->nombre_completo,
                    'departamento' => $empleado->departamento,
                    'puesto' => $empleado->puesto,
                    'rfc' => $empleado->rfc,
                    'nss' => $empleado->nss,
                    'sueldo_bruto' => $sueldoBruto,
                    'isr' => $isr,
                    'imss' => $imss,
                    'infonavit' => $infonavit,
                    'fonacot' => $fonacot,
                    'prestamo' => $prestamo,
                    'otras' => $otras,
                    'total_retenciones' => $totalRetenciones,
                    'neto' => $neto,
                ]);
            }

            $rows->push([
                'numero_empleado' => '',
                'nombre_completo' => 'TOTALES GENERALES',
                'departamento' => '',
                'puesto' => '',
                'rfc' => '',
                'nss' => '',
                'sueldo_bruto' => $totales['sueldo_bruto'],
                'isr' => $totales['isr'],
                'imss' => $totales['imss'],
                'infonavit' => $totales['infonavit'],
                'fonacot' => $totales['fonacot'],
                'prestamo' => $totales['prestamo'],
                'otras' => $totales['otras'],
                'total_retenciones' => $totales['total_retenciones'],
                'neto' => $totales['neto'],
            ]);

            $this->totalFilas = $rows->count();

            return $rows;
        } catch (Throwable $e) {
            throw new \RuntimeException('No se pudo generar el reporte de retenciones: ' . $e->getMessage(), 0, $e);
        }
    }

    public function headings(): array
    {
        $periodo = $this->meta['periodo'] ?? '-';
        $rango = $this->meta['rango'] ?? '-';

        return [
            ['Empresa:', $this->meta['nombre_empresa'] ?? '-'],
            ['RFC:', $this->meta['rfc_empresa'] ?? '-'],
            ['Reporte:', 'Reporte de Retenciones de Nómina'],
            ['Período:', $periodo . ' (' . $rango . ')'],
            ['Fecha de emisión:', $this->meta['fecha_emision'] ?? now()->format('d/m/Y H:i')],
            ['Generado por:', $this->meta['usuario'] ?? '-'],
            [],
            [
                'No. Empleado',
                'Nombre Completo',
                'Sucursal',
                'Puesto',
                'RFC',
                'NSS',
                'Sueldo Bruto',
                'ISR',
                'IMSS',
                'INFONAVIT',
                'FONACOT',
                'Préstamo',
                'Otras',
                'Total Retenciones',
                'Neto',
            ],
        ];
    }

    protected function resolverMetaNomina(int $id): array
    {
        $nominaEnc = Nominas_pagosenc::find($id);

        if (!$nominaEnc) {
            throw new \RuntimeException("No se encontró la nómina con ID {$id}.");
        }

        $empresa = collect(DB::select('
            SELECT
                enc.nombre_nomina,
                enc.fecha_inicio,
                enc.fecha_fin,
                tn.tipo AS periodo,
                e.nombre_empresa,
                e.rfc
            FROM tblnominas_pagoenc enc
            LEFT JOIN tblempresas e ON e.id = enc.id_empresa
            LEFT JOIN tbltipo_nominas tn ON tn.id = enc.idtiponomina
            WHERE enc.id = ?
            LIMIT 1
        ', [$id]))->first();

        if (empty($empresa?->nombre_empresa)) {
            $empresaFallback = collect(DB::select('
                SELECT e.nombre_empresa, e.rfc
                FROM tblnominas_pagodet det
                INNER JOIN tblempleados emp ON emp.id = det.idempleado
                INNER JOIN tblnominas nom ON nom.idempleado = emp.id
                INNER JOIN tblempresas e ON e.id = nom.idempresa
                WHERE det.idpagonomina = ?
                LIMIT 1
            ', [$id]))->first();

            if ($empresaFallback) {
                $empresa->nombre_empresa = $empresaFallback->nombre_empresa;
                $empresa->rfc = $empresaFallback->rfc;
            }
        }

        $fechaInicio = $nominaEnc->fecha_inicio
            ? Carbon::parse($nominaEnc->fecha_inicio)->format('d/m/Y')
            : '-';
        $fechaFin = $nominaEnc->fecha_fin
            ? Carbon::parse($nominaEnc->fecha_fin)->format('d/m/Y')
            : '-';

        return [
            'nombre_empresa' => $empresa->nombre_empresa ?? 'Sin empresa asignada',
            'rfc_empresa' => $empresa->rfc ?? '-',
            'periodo' => $empresa->periodo ?? '-',
            'rango' => $fechaInicio . ' - ' . $fechaFin,
            'fecha_emision' => now()->format('d/m/Y H:i'),
            'usuario' => Auth::user()->name ?? 'Sistema',
            'nombre_nomina' => $nominaEnc->nombre_nomina ?? 'NOMINA',
        ];
    }

    protected function calcularSueldoBruto(object $empleado): float
    {
        return (float) $empleado->total_sueldo
            + (float) $empleado->total_horas_extras
            + (float) $empleado->despensa
            + (float) $empleado->otros
            + (float) $empleado->percepcion_extraordinaria
            + (float) $empleado->pago_dias_descanso
            + (float) $empleado->pago_prima_dominical
            + (float) $empleado->pago_dias_vacaciones
            + (float) $empleado->pago_prima_vacacional;
    }
}
