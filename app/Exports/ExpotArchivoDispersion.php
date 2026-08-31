<?php

namespace App\Exports;

use App\Models\Empleados;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\NominaTraits;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use DB;

class ExpotArchivoDispersion implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    use NominaTraits;
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


                $cellRange = 'A1:F1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $cells = 'A1:F1';
                $event->sheet->getDelegate()->getStyle($cells)->getFont()->setBold(true);
                $event->sheet->getStyle('A1:F1')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ]
                ]);

            

                 //CONTADOR
                 $contador = 1;
                 $varlistanomina = $this-> ListadoDispersionExcel($this->id);
                 foreach ($varlistanomina as $item){
                     $contador = $contador + 1;
                 }
                 
                 $celdas2 = "C2:C".$contador;
                 $event->sheet->getStyle($celdas2)->getNumberFormat()->setFormatCode('###0.00');

                
                 //BORDER
                 $celdas = "A1:F".$contador;
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
        $varlistanomina = $this-> ListadoDispersionExcel($this->id);
        return $varlistanomina;
    }
    

    public function headings(): array
    {
        return [
            [
                'No. DE EMPLEADO',
                'NOMBRE EMPLEADO',
                'IMPORTE',
                'No. DE BANCO',
                'TIPO DE CUENTA',
                'No DE CUENTA'
            ]
        ];
    }

   
}
