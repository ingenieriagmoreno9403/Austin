<?php

namespace App\Exports;

use App\Models\Empleados;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\NominaTraits;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use DB;
use Maatwebsite\Excel\Concerns\WithMapping;

class AguinaldosExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithMapping
{

    use NominaTraits;
    use Exportable;
    protected $id;
    

    function __construct($id) {
            $this->id = $id;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
   
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {

                // Encabezado
                $cellRange = 'A1:V1'; // All headers (22 columnas)
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $cells = 'A1:V1';
                $event->sheet->getDelegate()->getStyle($cells)->getFont()->setBold(true);
                $event->sheet->getDelegate()->setAutoFilter('A1:V1');
                $event->sheet->getStyle('A1:V1')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => '366092'], // Azul marino
                    ],
                    'font' => [
                        'color' => ['argb' => 'FFFFFF'], // Blanco
                    ],
                ]);

                // CONTADOR
                $contador = 1;
                $varlistaaguinaldos = $this->obtener_aguinaldos_det($this->id);
                foreach ($varlistaaguinaldos as $item){
                    $contador = $contador + 1;
                }
                
                // Formato de números para columnas monetarias
                // I=Salario F., J=Salario E., K=Salario Diario, O= Aguinaldo Fiscal en adelante
                $event->sheet->getStyle("I2:K".$contador)->getNumberFormat()->setFormatCode('$#,##0.00');
                $event->sheet->getStyle("O2:V".$contador)->getNumberFormat()->setFormatCode('$#,##0.00');

                // Formato de números para días
                $event->sheet->getStyle("H2:H".$contador)->getNumberFormat()->setFormatCode('#,##0');
                $event->sheet->getStyle("N2:N".$contador)->getNumberFormat()->setFormatCode('#,##0');

                // Formato de fecha
                $event->sheet->getStyle("G2:G".$contador)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                // BORDES
                $celdas = "A1:V".$contador;
                $event->sheet->getDelegate()->getStyle($celdas)
                 ->getActiveSheet()
                 ->getStyle($celdas)
                 ->getBorders()
                 ->getAllBorders()
                 ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            },
        ];
    }


    public function collection()
    {
        $varlistaaguinaldos = $this->obtener_aguinaldos_det($this->id);
        return $varlistaaguinaldos;
    }

    public function map($item): array
    {
        // Calcular salario excedente (sueldo_diario - salario_fijo)
        $salario_excedente = (($item->sueldo_diario ?? 0) - ($item->salario_fijo ?? 0));
        
        return [
            $item->idempleado ?? '',
            $item->nombre_empleado ?? '',
            $item->puesto ?? '',
            $item->banco ?? '',
            $item->idbanca ?? '',
            $item->numero_cuenta ?? '',
            isset($item->fecha_ingreso) ? \Carbon\Carbon::parse($item->fecha_ingreso)->format('d/m/Y') : '',
            $item->dias_trabajados ?? 0,
            $item->salario_fijo ?? 0, // Salario F.
            $salario_excedente, // Salario E. (calculado)
            $item->sueldo_diario ?? 0, // Salario Diario
            $item->sueldo_mensual ?? 0, // Sueldo Mensual
            $item->dias_aguinaldo_pagados ?? 0,
            $item->aguinaldo_f ?? 0,
            $item->aguinaldo_e ?? 0,
            $item->aguinaldo_gravado ?? 0,
            $item->aguinaldo_exento ?? 0,
            $item->aguinaldo_total ?? 0,
            $item->isr_calculado ?? 0,
            $item->total_pagar_f ?? 0,
            $item->total_pagar_e ?? 0,
            $item->total_pagar ?? 0,
        ];
    }
    

    public function headings(): array
    {
        return [
            [
                'No. Empleado',
                'Nombre Empleado',
                'Puesto',
                'Banco',
                'ID Banca',
                'Numero de Cuenta',
                'Fecha Ingreso',
                'Días Trabajados',
                'Salario F.',
                'Salario E.',
                'Salario Diario',
                'Sueldo Mensual',
                'Días Aguinaldo Pagados',
                'Aguinaldo Fiscal',
                'Aguinaldo Excedente',
                'Aguinaldo Gravado',
                'Aguinaldo Exento',
                'Aguinaldo Total',
                'ISR Calculado',
                'Total Pagar Fiscal',
                'Total Pagar Excedente',
                'Total Pagar',
            ]
        ];
    }

   
}

