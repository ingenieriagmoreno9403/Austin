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

class DisValerasExport implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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
                $event->sheet->getDelegate()->getStyle('A2:S2')->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle('A1:S2')->getFont()->setBold(true);
                $event->sheet->mergeCells('A1:E1');
                $event->sheet->getDelegate()->getStyle('A1:D1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ff00b0f0');//AZUL 

                $event->sheet->getDelegate()->getStyle('A2:S2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ff00b0f0');//AZUL 

                $event->sheet->getDelegate()->setAutoFilter('A2:S2');
                $event->sheet->getStyle('A2:S2')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff000000'],
                        ],
                    ]
                ]);



      
                $event->sheet->getDelegate()->getStyle('A1:S2')->getFont()->getColor()
                ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
                        
            },
        ];
    }


    public function collection()
    {
        $obtenerValDis=  $this->exportarValDis();
        return $obtenerValDis;
    }

    public function headings(): array
    {
        return [
            ['CATALOGO GENERAL DE VALERAS ENTRGADAS'],
            [
                'ID ENTREGA',
                'ID DV',
                'NOMBRE DV',
                'ESTADO DE ENTREGA',
                'TIPO DV',
                'CAPITAL',
                'VALERA ENTREGADA',
                'FOLIO INICIO',
                'FOLIO FIN',
                'VALES DISPONIBLES',
                'VALES USADOS',
                'TOTAL VALES',
                'SUCURSAL',
                'NOMBRE COORDINADOR',
                'FECHA ENTREGA',
                'CREADA POR',
                'FECHA DE CREACIÓN',
                'ACTUALIZADO POR',
                'FECHA DE ACTUALIZACIÓN'
            ]
        ];
    }


   
}
