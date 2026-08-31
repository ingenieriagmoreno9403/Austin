<?php

namespace App\Exports;

use App\Models\Empleados;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Traits\ServiciosTrait;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use DB;


class ComparativaExportar implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents,WithDrawings
{

    use ServiciosTrait;
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

     
                $event->sheet->getDelegate()->getStyle('A1:C1')->getFont()->setSize(28);
                $event->sheet->getDelegate()->getStyle('B1')->getFont()->setBold(true);

                $event->sheet->getDelegate()->getStyle('A2:C3')->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle('A2')->getFont()->setBold(true);

                $event->sheet->getDelegate()->getStyle('A3:K3')->getFont()->setSize(12);
                $event->sheet->mergeCells('A3:C3');
                

                $event->sheet->getDelegate()->getStyle('A3:C3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FFF301');//amarillo 

                $event->sheet->getDelegate()->getStyle('A4:K4')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FF5601');//naranja  

                $event->sheet->getDelegate()->setAutoFilter('A4:K4');
                $event->sheet->getStyle('A4:K4')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff000000'],
                        ],
                    ]
                ]);
                $event->sheet->getDelegate()->getStyle('A4:K4')->getFont()->getColor()
                ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
                $event->sheet->getDelegate()->getStyle('A4:K4')->getFont()->setBold(true);

                $contador = 0;
                $filas =  $this->ComparativaPreciosExport($this->id);
                foreach ($filas as $key) {
                   $contador = $contador + 1;
                }

                $contador = $contador + 4;
                
                $celdas = "A4:K".$contador;

                //BORDER
                $event->sheet->getDelegate()->getStyle($celdas)
                ->getActiveSheet()
                ->getStyle($celdas)
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                $celdas = "E4:E".$contador;
                $event->sheet->getStyle($celdas)->getNumberFormat()->setFormatCode('$#,##0.00');

                $celdas = "G4:J".$contador;
                $event->sheet->getStyle($celdas)->getNumberFormat()->setFormatCode('$#,##0.00');
                        
            },
        ];

        
    }


  

    public function drawings() {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('This is my logo');
        $drawing->setPath(public_path('/Images/ummining_500.png'));
        $drawing->setHeight(50);
        $drawing->setCoordinates('A1');

        return [$drawing];
    }


    public function collection()
    {
        $obtener =  $this->ComparativaPreciosExport($this->id);
        return $obtener;
    }

    public function headings(): array
    {
        $date = Carbon::now()->format('d/m/Y');
        return [
            [' ','Comparativa de Precios'],
            [' FECHA',$date],
            ['UM MINING agradece su concideración para cotizar su requerimiento'],
            [
                'PROVEEDOR',
                'PRODUCTO',
                'CANTIDAD',	
                'UNIDAD',
                'PRECIO',	
                'MARGEN',	
                'ENVIO',	
                'COST + MARGEN',	
                'SUBTOTAL',	
                'TOTAL',	
                'OBSERVACION'
            ]
        ];
    }


   
}
