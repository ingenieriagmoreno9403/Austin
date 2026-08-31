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

class cuentaHistorial implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    use DatosimpleTraits;
    use Exportable;

    protected $id;
    protected $fecha_inicio;
    protected $fecha_fin;
    protected $nombre;
    protected $empresa;

    function __construct($id, $fecha_inicio, $fecha_fin,$nombre ,$empresa) {
            $this->id = $id;
            $this->fecha_inicio = $fecha_inicio;
            $this->fecha_fin = $fecha_fin;
            $this->nombre = $nombre;
            $this->empresa = $empresa;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
   
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {

     
                $event->sheet->getDelegate()->getStyle('A1:D1')->getFont()->setSize(14);
                $event->sheet->getDelegate()->getStyle('A1:D1')->getFont()->setBold(true);
                $event->sheet->mergeCells('A1:D1');
                $event->sheet->getDelegate()->getStyle('A1:D1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ff00b0f0');//AZUL 

                $event->sheet->getDelegate()->getStyle('A2:D2')->getFont()->setSize(14);
                $event->sheet->getDelegate()->getStyle('A2:D2')->getFont()->setBold(true);
                $event->sheet->mergeCells('A2:D2');
                $event->sheet->getDelegate()->getStyle('A2:D2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ff00b0f0');//AZUL 

                $event->sheet->getDelegate()->setAutoFilter('A3:D3');
                $event->sheet->getDelegate()->setAutoFilter('G3:M3');
                $event->sheet->getStyle('A3:M4')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff000000'],
                        ],
                    ]
                ]);


                $event->sheet->getStyle('A4:M4')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff000000'],
                        ],
                    ]
                ]);

      
                $event->sheet->getDelegate()->getStyle('A1:M4')->getFont()->getColor()
                ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
                        

                $event->sheet->getDelegate()->getStyle('A3:M3')->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle('A3:M3')->getFont()->setBold(true);
                $event->sheet->mergeCells('F3:G3');
                $event->sheet->getDelegate()->getStyle('A4:M4')->getFont()->setSize(9);
                $event->sheet->getDelegate()->getStyle('A3:M4')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ff00b0f0');//AZUL 

               
            },
        ];
    }


    public function collection()
    {
        $obtenerHistorialCuentas=  $this->ExcelHistorialCuentas($this->id, $this->fecha_inicio, $this->fecha_fin);
        return $obtenerHistorialCuentas;
    }

    public function headings(): array
    {
        return [
            [$this->empresa],
            ['MOVIMIENTOS DE CUENTA'],
            [
                'FECHA',
                'EMPRESA',
                'CUENTA',
                'CONCEPTO',
                'TITULAR',
                'MOVIMIENTOS',
                '', 
                'SALDO',
                '# POLIZA',
                '# REFERENCIA',
                'ESTADO',
                'USUARIO',
                'NOMBRE DEL USUARIO'
            ],
            [
                ' ',
                ' ',
                ' ',
                ' ',
                ' ',
                'INGRESOS / PAGOS',
                'CARGO / DESEMBOLSO', 
                ' ',
                ' ',
                ' ',
                ' ',
                ' ',
                ' '
            ]
        ];
    }


   
}
