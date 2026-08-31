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
use App\Models\prestamos_valesenc;
use Carbon\Carbon;

use DB;

class rpttetnom implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    use DatosimpleTraits;
    use Exportable;

    protected $date;

    function __construct($date) {
            $this->date = $date;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
   
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {


                $cellRange = 'A1:G11'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(16);
                $cells = 'A2:WW2';
                $event->sheet->getDelegate()->getStyle($cells)->getFont()->setBold(true);
                $event->sheet->getDelegate()->setAutoFilter('A2:E2');
                $event->sheet->getStyle('B3:G10')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ffd9d9d9'],
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

                $event->sheet->getDelegate()->getStyle('A1:G1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffe6b8b7');//ROJO

                $event->sheet->getDelegate()->getStyle('A1:G1')->getFont()->setSize(18);
                $event->sheet->getDelegate()->getStyle('A1:G1')->getFont()->setBold(true);
                $event->sheet->mergeCells('A1:G1');

                $event->sheet->getDelegate()->getStyle('A2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('95B3D7');//AZUL

                $event->sheet->getDelegate()->getStyle('B2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffc6e0b4');//VERDE

                $event->sheet->getDelegate()->getStyle('C2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffb8cce4');//MORADO AZULADO

                $event->sheet->getDelegate()->getStyle('D2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffd9d9d9');//GRIS

                $event->sheet->getDelegate()->getStyle('E2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffc6e0b4');//VERDE

                $event->sheet->getDelegate()->getStyle('F2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('B1A0C7');//ROJO

                $event->sheet->getDelegate()->getStyle('G2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('fffabf8f');//NARANJA


                $event->sheet->getDelegate()->getStyle('A11:G11')->getFont()->setBold(true);

                $event->sheet->getDelegate()->getStyle('B11:G11')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffb7dee8');//MORADO

                $event->sheet->getDelegate()->getStyle('A3:A10')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffd9d9d9');//GRIS

                $event->sheet->getDelegate()->getStyle('A11')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffc6e0b4');//VERDE

             
            },
        ];
    }


    public function collection()
    {
        $listaodp=$this->tptresumenretenciones($this->date);
        return $listaodp;
   
    }

    public function headings(): array
    {
        return [
            ['RESUMEN RETENCIONES QUINCENAL POR SUCURSAL'],
            [
                'SUCURSAL',
                'SUELDO FISCAL',
                'DEUDORES FISCAL',          
                'INFONAVIT',
                'IMSS',
                'ISR',
                'NOMINA FISCAL'        
            ]
        ];
    }


   
}
