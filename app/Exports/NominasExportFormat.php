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

class NominasExportFormat implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    use DatosimpleTraits;
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


                $cellRange = 'A1:K1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $cells = 'A1:K1';
                $event->sheet->getDelegate()->getStyle($cells)->getFont()->setBold(true);
                $event->sheet->getDelegate()->setAutoFilter('A1:K1');
                $event->sheet->getStyle('A1:K1')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ]
                ]);

            
                $event->sheet->getDelegate()->getStyle('A1:B1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ffc000');

                $event->sheet->getDelegate()->getStyle('C1:G1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('C4D79B');


                $event->sheet->getDelegate()->getStyle('H1:I1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('DA9694');

                $event->sheet->getDelegate()->getStyle('J1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('92CDDC');

                $event->sheet->getDelegate()->getStyle('K1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('C4D79B');

                 //CONTADOR
                 $contador = 1;
                 $varlistanomina = $this-> obtenerformatnominasporidexport($this->id);
                 foreach ($varlistanomina as $item){
                     $contador = $contador + 1;
                 }
                 
                 $celdas2 = "C2:C".$contador;
                 $event->sheet->getStyle($celdas2)->getNumberFormat()->setFormatCode('#,##0.00');

                 $celdas3 = "D2:F".$contador;
                 $event->sheet->getStyle($celdas3)->getNumberFormat()->setFormatCode('#,##0');

                 $celdas2 = "G2:G".$contador;
                 $event->sheet->getStyle($celdas2)->getNumberFormat()->setFormatCode('#,##0.00');

                 $celdas2 = "H2:I".$contador;
                 $event->sheet->getStyle($celdas2)->getNumberFormat()->setFormatCode('#,##0.00');

                 $celdas3 = "J2:J".$contador;
                 $event->sheet->getStyle($celdas3)->getNumberFormat()->setFormatCode('#,##0');

                 $celdas2 = "K2:K".$contador;
                 $event->sheet->getStyle($celdas2)->getNumberFormat()->setFormatCode('#,##0.00');

                 //BORDER
                 $celdas = "A1:K".$contador;
                 $event->sheet->getDelegate()->getStyle($celdas)
                 ->getActiveSheet()
                 ->getStyle($celdas)
                 ->getBorders()
                 ->getAllBorders()
                 ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            },
        ];
    }


    public function collection()
    {
        $varlistanomina = $this-> obtenerformatnominasporidexport($this->id);
        return $varlistanomina;
    }
    

    public function headings(): array
    {
        return [
            [
                'id',
                'empleado',
                'bono',
                'horas_extras',
                'dias_descanso',
                'dias_prima_dominical',
                'percepcion_exenta',
                'prestamo_empresarial',
                'fonacot',
                'dias_prima_vacacional',
                'viaticos',
            ]
        ];
    }

   
}
