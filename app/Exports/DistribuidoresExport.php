<?php

namespace App\Exports;

use App\Models\cuentas;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

use DB;

class DistribuidoresExport implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    use Exportable;

    /**
    * @return \Illuminate\Support\Collection
    */
   
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {

     
                $event->sheet->getDelegate()->getStyle('A1:D1')->getFont()->setSize(14);
                $event->sheet->getDelegate()->getStyle('A2:BO2')->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle('A1:BO2')->getFont()->setBold(true);
                $event->sheet->mergeCells('A1:E1');
                $event->sheet->getDelegate()->getStyle('A1:D1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ff00b0f0');//AZUL 

                $event->sheet->getDelegate()->getStyle('A2:BO2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ff00b0f0');//AZUL 

                $event->sheet->getDelegate()->setAutoFilter('A2:BO2');
                $event->sheet->getStyle('A2:BO2')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff000000'],
                        ],
                    ]
                ]);



      
                $event->sheet->getDelegate()->getStyle('A1:BO2')->getFont()->getColor()
                ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
                        
            },
        ];
    }


    public function collection()
    {
        $obtenerDis=  $this->exportarDis();
        return $obtenerDis;
    }

    public function headings(): array
    {
        return [
            ['CATALOGO GENERAL DE DISTRIBUIDORES'],
            [
                'ID', 
                'NOMBRE COMPLETO',
                'ESTADO',
                'TIPO',
                'ID COORD', 
                'NOMBRE COORD', 
                'SUCURSAL',
                'CAPITAL SOLICITADO',
                'CAPITAL ACTUAL',
                'CAPITAL AUTORIZADO',

                'ESTADO CIVIL',
                'SEXO',
                'LUGAR DE NACIMIENTO',
                'FECHA DE NACIMIENTO',
                'NACIONALIDAD',
                'CURP',
                'RFC',
                'TELEFONO',
                'DIRECCIÓN',
            
                'LUGAR DE EMPLEO',
                'PUESTO',
                'SALARIO MENSUAL',
                'EGRESO FIJO MENSUAL',
                'ANTIGUEDAD',
                'TELEFONO EMPRESA',
                'DIRECCIÓN EMPRESA',
                

        
                'ID CONYUGE',
                'NOMBRE CONYUGE', 
                'FECHA DE NACIMIENTO',
                'CURP',
                'REFC',
                'SEXO',
                'TELEFONO',
                'LUGAR EMPLEO',
                'PUESTO',
                'SALARIO MENSUAL',
                'EGRESO FIJO MENSUAL',
                'ANTIGUEDAD',
                'TELEFONO EMPRESA',
                'DIRECCIÓN EMPRESA',

                'ID AVAL',
                'NOMBRE AVAL', 
                'FECHA DE NACIMIENTO',
                'LUGAR DE NACIMIENTO',
                'NACIONALIDAD',
                'CURP',
                'RFC',
                'SEXO',
                'ESTADO CIVIL',
                'TELEFONO',
                'DIRECCION',
                'LUGAR DE EMPLEO',
                'PUESTO DE EMPLEO',
                'SALARIO MENSUAL',
                'EGRESO FIJO MENSUAL',
                'ANTIGUEDAD',
                'TELEFONO EMPRESA',
                'DIRECCIÓN EMPRESA',
                

                'ID REFERENCIA',
                'NOMBRE REFERENCIA', 
                'FECHA NACIMEINTO',
                'TELEFONO',
                'DIRECCIÓN',


        
                'FECHA DE CREACION',
                'CREADO POR',
                'FECHA DE ACTUALIZACIÓN',
                'ACTUALIZADO POR',
            ]
        ];
    }


   
}
