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

class CreditosEmpPend implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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
                ->setRGB('ffb8cce4');//MORADO AZULADO

                $event->sheet->getDelegate()->getStyle('E2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffb7dee8');//AZUL MENTA

                $event->sheet->getDelegate()->getStyle('F2:G2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffc6e0b4');//VERDE


                $event->sheet->getDelegate()->getStyle('H2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffb7dee8');//AZUL MENTA

                $event->sheet->getDelegate()->getStyle('I2:L2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('fffabf8f');//NARANJA

                $event->sheet->getDelegate()->getStyle('M2:O2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffc6e0b4');//VERDE

                $event->sheet->getDelegate()->getStyle('P2:R2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffccc0da');//MORADO
            },
        ];
    }


    public function collection()
    {
        $varPresEnc= $this->ExcelTempPrestEmpEnc();
        return $varPresEnc;
    }
    

    public function headings(): array
    {
        return [
            ['CREDITOS CALCULADOS DE EMPLEADOS'],
            [
                'NO. CREDITO',
                'NO. EMPLEADO',
                'NOMBRE COMPLETO EMPLEADO',
                'FECHA INICIO',
                'TIPO DE CREDITO', 
                'TIPO PLAZO',
                'PLAZOS',  
                'MONTO',
                'TASA',
                'PORCENTAJE',
                'INTERES',
                'IVA INTERES',
                'TOTAL',
                'INTERES REDONDEADO',
                'TOTAL REDONDEADO',
                'CREDITO AUTORIZADO POR',
                'COMENTARIO',
                'FECHA DE CREACION'
            ]
        ];
    }

   
}
