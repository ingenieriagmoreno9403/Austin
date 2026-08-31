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
use App\Traits\ReportesTraits;
use App\Models\prestamos_valesenc;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use DB;

class ExportarGastos implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents,WithDrawings
{

    use DatosimpleTraits;
    use Exportable;
    use ReportesTraits;

    protected $fechaIni;
    protected $fechaFin;

    function __construct($fechaIni, $fechaFin) {
            $this->fechaIni = $fechaIni;
            $this->fechaFin = $fechaFin;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
   
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                 $event->sheet->getDelegate()->getStyle('A1:N4')->getFont()->getColor()
                ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
                $event->sheet->mergeCells('A1:A3');

                $event->sheet->getDelegate()->getStyle('B1:D2')->getFont()->setSize(18);
                $event->sheet->getDelegate()->getStyle('B1:D2')->getFont()->setBold(true);
                $event->sheet->mergeCells('B1:D2');

                $event->sheet->getDelegate()->getStyle('B3:D3')->getFont()->setSize(13);
                $event->sheet->getDelegate()->getStyle('B3:D3')->getFont()->setBold(true);
                $event->sheet->mergeCells('B3:D3');
                $event->sheet->getDelegate()->getStyle('B1:D3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FF6315');//NARANJA
                

                $event->sheet->getStyle('B1:D3')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff000000'],
                        ],
                    ]
                ]);

                $event->sheet->getDelegate()->setAutoFilter('A4:N4');
                $event->sheet->getStyle('A4:N4')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff000000'],
                        ],
                    ]
                ]);

                        
                $event->sheet->getDelegate()->getStyle('A4:N4')->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle('A4:N4')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A4:N4')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FF6315');//NARANJA 

                //CONTADOR
                $contador = 4;
                $varlista =  $this->exportarReporte_gastos($this->fechaIni, $this->fechaFin);
                foreach ($varlista as $item){
                    $contador = $contador + 1;
                }
                $celdas = "A4:N".$contador;


                //BORDER
                $event->sheet->getDelegate()->getStyle($celdas)
                ->getActiveSheet()
                ->getStyle($celdas)
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                $celdas = "E5:I".$contador;
                $event->sheet->getStyle($celdas)->getNumberFormat()->setFormatCode('$#,##0.00');

                // $celdas_final = "A".$contador.":I".$contador;
                // $event->sheet->getDelegate()->getStyle($celdas_final)->getFont()->setSize(13);
                // $event->sheet->getDelegate()->getStyle($celdas_final)->getFont()->setBold(true);
                // $event->sheet->mergeCells($celdas_final);

                // $celdas_totales = "J".$contador.":T".$contador;
                // $event->sheet->getDelegate()->getStyle($celdas_totales)->getFont()->setSize(13);
                // $event->sheet->getDelegate()->getStyle($celdas_totales)->getFont()->setBold(true);
                // $event->sheet->getStyle($celdas_totales)->getNumberFormat()->setFormatCode('$#,##0.00');

                // $event->sheet->getDelegate()->getStyle($celdas_final)
                // ->getFill()
                // ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                // ->getStartColor()
                // ->setRGB('ff00b0f0');//AZUL

                // $event->sheet->getDelegate()->getStyle($celdas_final)->getFont()->getColor()
                // ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
            },
        ];
    }


    public function collection()
    {
        $exportarReporte_gastos =  $this->exportarReporte_gastos($this->fechaIni, $this->fechaFin);
        return $exportarReporte_gastos;
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

    public function headings(): array
    {
        // $contador = 0;
        // $varlista =  $this->obtenerReporte_dseembolsos($this->fechaIni, $this->fechaFin);
        // foreach ($varlista as $item){
        //     $contador = $contador + 1;
        // }

        return [
            [' ','REPORTE DE GASTOS '],
            [' '],
            [' ','PERIODO '.$this->fechaIni.' AL '.$this->fechaFin],
            [
                'FECHA DE MOVIMIENTO',
                'CUENTA O CAJA',
                'CONCEPTO',
                'SUCURSAL',
                'IMPORTE DEL GASTO',
                'IVA',
                'RETENCIÓN IVA',
                'RETENCIÓN ISR',
                'TOTAL',
                'NO. POLIZA',
                'ID DE GASTO',
                'NOMBRE DE GASTO',
                'USUARIO REGISTRADO', 
                'NOMBRE USUARIO'
            ]
        ];
    }


   
}
