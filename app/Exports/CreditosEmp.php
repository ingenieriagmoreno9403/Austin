<?php

namespace App\Exports;

use App\Models\Empleados;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\DatosimpleTraits;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use DB;

class CreditosEmp implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    use DatosimpleTraits;
    use Exportable;

    /**
    * @return \Illuminate\Support\Collection
    */
   
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {


                $cellRange = 'A1:W1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
                $cells = 'A2:WW2';
                $event->sheet->getDelegate()->getStyle('A1')->getFont()->setBold(true);
                $event->sheet->mergeCells('A1:C1');
                $event->sheet->getDelegate()->setAutoFilter('A2:R2');
                $event->sheet->getStyle('A2:R2')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff404040'],
                        ],
                    ]
                ]);

                // ->setRGB('ffb8cce4');//MORADO AZULADO
                // ->setRGB('fffabf8f');//NARANJA
                // ->setRGB('ffc6e0b4');//VERDE
                // ->setRGB('ffb7dee8');//AZUL MENTA
                // ->setRGB('ffe6b8b7');//ROJO
                // ->setRGB('ffccc0da');//MORADO
                // ->setRGB('ffd9d9d9');//GRIS

                $event->sheet->getDelegate()->getStyle('R1:W1')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('R1:W1')->getFont()->setSize(11);
                $event->sheet->mergeCells('R1:T1');
                $event->sheet->mergeCells('U1:W1');
                $event->sheet->getDelegate()->getStyle('R1:T1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffd9d9d9');//GRIS

                $event->sheet->getDelegate()->getStyle('U1:W1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffd9d9d9');//GRIS

                //----------------------------------------------------------------
               
                $event->sheet->getDelegate()->getStyle('A2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffb7dee8');//AZUL MENTA

                $event->sheet->getDelegate()->getStyle('B2:C2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffccc0da');//MORADO


                $event->sheet->getDelegate()->getStyle('D2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffc6e0b4');//VERDE

                $event->sheet->getDelegate()->getStyle('E2:F2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffb8cce4');//MORADO AZULADO

                $event->sheet->getDelegate()->getStyle('G2:I2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffc6e0b4');//VERDE

                // $event->sheet->getDelegate()->getStyle('H2:I2')
                // ->getFill()
                // ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                // ->getStartColor()
                // ->setRGB('ffb8cce4');//MORADO AZULADO

                $event->sheet->getDelegate()->getStyle('J2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffb8cce4');//MORADO AZULADO


                $event->sheet->getDelegate()->getStyle('K2:N2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('fffabf8f');//NARANJA

                $event->sheet->getDelegate()->getStyle('O2:Q2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffc6e0b4');//VERDE

                $event->sheet->getDelegate()->getStyle('R2:T2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffb8cce4');//MORADO AZULADO

                $event->sheet->getDelegate()->getStyle('U2:W2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffb7dee8');//AZUL MENTA

                $event->sheet->getDelegate()->getStyle('X2:Z2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffccc0da');//MORADO
            },
        ];
    }


    public function collection()
    {
        $varPresEnc= $this->ExcelPrestEmpEnc();
        return $varPresEnc;
    }
    

    public function headings(): array
    {
        return [
            ['CREDITOS CALCULADOS DE EMPLEADOS',
            '','','','','','','','','','','','','','','','',
            'ARCHIVOS SUBIDOS','','',
            'ARCHIVOS GUARDADOS','',''
            ],
            [
                'NO. CREDITO',
                'NO. EMPLEADO',
                'NOMBRE COMPLETO EMPLEADO',
                'ESTADO',
                'FECHA INICIO',
                'FECHA FIN',
                'TIPO DE CREDITO', 
                'PLAZOS',
                'PAGO QUINCENAL',  
                'MONTO DE PRESTAMO',
                'TASA',
                'PORCENTAJE',
                'INTERES',
                'IVA INTERES',
                'TOTAL',
                'INTERES REDONDEADO',
                'TOTAL REDONDEADO',

                'ESTADO DE CUENTA',
                'PAGARE',
                'FACTURA',

                'ESTADO DE CUENTA',
                'PAGARE',
                'FACTURA',

                'CREDITO AUTORIZADO POR',
                'COMENTARIO',
                'FECHA DE CREACION'
            ]
        ];
    }

   
}
