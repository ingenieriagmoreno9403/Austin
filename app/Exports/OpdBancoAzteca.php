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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use App\Models\prestamos_valesenc;
use Carbon\Carbon;

use DB;

class OpdBancoAzteca implements   FromCollection, ShouldAutoSize, WithStyles
{

    use DatosimpleTraits;
    use Exportable;

    protected $date;

    function __construct($date) {
            $this->date = $date;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
   
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {


                // $cellRange = 'A1:W1'; // All headers
                // $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
                // $cells = 'A2:WW2';
                // $event->sheet->getDelegate()->getStyle($cells)->getFont()->setBold(true);
                // $event->sheet->getDelegate()->setAutoFilter('A2:AF2');
                $event->sheet->getStyle('A1')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ]
                ]);
                    
            },

            BeforeExport::class  => function(BeforeExport $event) {
                $event->writer->getDelegate()->getSecurity()->setLockWindows(true);
                $event->writer->getDelegate()->getSecurity()->setLockStructure(true);
                $event->writer->getDelegate()->getSecurity()->setWorkbookPassword("Your password");
            }
        ];

       
    }


    public function styles(Worksheet $sheet)
    {
        // Make sure you enable worksheet protection if you need any of the worksheet or cell protection features!
        $sheet->getParent()->getActiveSheet()->getProtection()->setSheet(true);
        
        // lock all cells then unlock the cell
        $sheet->getParent()->getActiveSheet()
            ->getStyle('T2:T10')
            ->getProtection()
            ->setLocked(Protection::PROTECTION_PROTECTED);

        // styling first row
        $sheet->getStyle(1)->getFont()->setBold(true);
    }


    public function collection()
    {
        $listaodp=  $this-> exportodpAzteca($this->date);
        $obtodpactivar=  $this-> obtodpactivarAzteca($this->date);
        
        foreach($obtodpactivar as $listadodeodp)
        {
        $fechaHoy = Carbon::now();
        $fechaActualMinutes = $fechaHoy->toDateTimeString();
        $prestamos = prestamos_valesenc::find($listadodeodp->idenc);
        $prestamos->otrosconceptos1 = 1;
        $prestamos->fecha_activacionODP = $fechaActualMinutes;
        $prestamos->updated_by = auth()->user()->name;
        $prestamos->save();
        }
        return $listaodp;
    }


}