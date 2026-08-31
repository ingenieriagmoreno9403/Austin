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

class ClientesExport implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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
                $event->sheet->getDelegate()->getStyle('A2:X2')->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle('A1:X2')->getFont()->setBold(true);
                $event->sheet->mergeCells('A1:E1');
                $event->sheet->getDelegate()->getStyle('A1:D1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ff00b0f0');//AZUL 

                $event->sheet->getDelegate()->getStyle('A2:X2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ff00b0f0');//AZUL 

                $event->sheet->getDelegate()->setAutoFilter('A2:X2');
                $event->sheet->getStyle('A2:X2')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff000000'],
                        ],
                    ]
                ]);



      
                $event->sheet->getDelegate()->getStyle('A1:X2')->getFont()->getColor()
                ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
                        
            },
        ];
    }


    public function collection()
    {
        $obtenerCliente =  $this->exportarCatalogoClientes();
        return $obtenerCliente;
    }

    public function headings(): array
    {
        return [
            ['CATALOGO GENERAL DE CLIENTES'],
            [
                'ID CLIENTE',
                'NOMBRE CLIENTE',
                'ESTADO',
                'ID DISTRIBUIDOR',
                'NOMBRE DISTRIBUIDOR',
                'NOMBRE SUCURSAL',
                'FECHA NACIMIENTO',
                'CURP',
                'RFC',
                'DIRECCIÓN',
                'TELEFONO',
                'LUGAR DE EMPLEO',
                'TELEFONO DE EMPLEO',
                'NOMBRE DE REFERENCIA',
                'DIRECCIÓN DE REFERENCIA',
                'TELEFONO DE REFERENCIA',
                'FECHA DE ALTA CLIENTE', 
                'ID PRESTAMO ACTIVO',
                'FOLIO VALE',
                'MONTO DE VALE',
                'PLAZOS',
                'PAGO POR PLAZO',
                'PAGO TOTAL',
                'FECHA DE ALTA CREDITO'
            ]
        ];
    }


   
}
