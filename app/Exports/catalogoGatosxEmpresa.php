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

class catalogoGatosxEmpresa implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    use DatosimpleTraits;
    use Exportable;
    protected $id;
    protected $nombre;

    function __construct($id, $nombre) {
        $this->id = $id;
        $this->nombre = $nombre;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
   
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {

     
                $event->sheet->getDelegate()->getStyle('A1:F1')->getFont()->setSize(14);
                $event->sheet->getDelegate()->getStyle('A2:H2')->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle('A1:H2')->getFont()->setBold(true);
                $event->sheet->mergeCells('A1:E1');
                $event->sheet->getDelegate()->getStyle('A1:F1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FF9966');//NARANJA

                $event->sheet->getDelegate()->getStyle('A2:H2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FF9966');//NARANJA

                $event->sheet->getDelegate()->setAutoFilter('A2:H2');
                $event->sheet->getStyle('A2:H2')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff000000'],
                        ],
                    ]
                ]);



      
                $event->sheet->getDelegate()->getStyle('A1:H2')->getFont()->getColor()
                ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
                        
            },
        ];
    }


    public function collection()
    {
        $obtenerGastos=  $this->obtenerGastosxEmpresaExcel($this->id);
        return $obtenerGastos;
    }

    public function headings(): array
    {
        return [
            ['CATALOGO DE GASTOS '.$this->nombre],
            [
                '#',
                'ESTADO',
                'NOMBRE',
                'TIPO',
                'MANEJADO',
                'DIRIGIDO',
                'CREADO', 
                'ACTUALIZADO'
            ]
        ];
    }


   
}
