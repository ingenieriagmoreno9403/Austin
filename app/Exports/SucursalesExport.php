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

class SucursalesExport implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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

     
                $event->sheet->getDelegate()->getStyle('A1:D1')->getFont()->setSize(14);
                $event->sheet->getDelegate()->getStyle('A2:N2')->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle('A1:N2')->getFont()->setBold(true);
                $event->sheet->mergeCells('A1:E1');
                $event->sheet->getDelegate()->getStyle('A1:D1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FF9966');//NARANJA 

                $event->sheet->getDelegate()->getStyle('A2:N2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FF9966');//NARANJA

                $event->sheet->getDelegate()->setAutoFilter('A2:N2');
                $event->sheet->getStyle('A2:N2')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff000000'],
                        ],
                    ]
                ]);



      
                $event->sheet->getDelegate()->getStyle('A1:N2')->getFont()->getColor()
                ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
                        
            },
        ];
    }


    public function collection()
    {
        $obtenerGastos=  $this->obtenersucursalesExcel();
        return $obtenerGastos;
    }

    public function headings(): array
    {
        return [
            ['CATALOGO GENERAL DE SUCURSALES'],
            [
                '#',
                'EMPRESA',
                'NOMBRE',
                'CONDICION',
                'TELEFONO',
                'ESTADO',
                'CIUDAD',
                'COLONIA',
                'CALLE',
                'NO. INT',
                'NO. EXT',
                'C.P.', 
                'CREADO', 
                'ACTUALIZADO'
            ]
        ];
    }


   
}
