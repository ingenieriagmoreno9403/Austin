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

class MovimientosHistorial implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    use DatosimpleTraits;
    use Exportable;

    protected $id;
    protected $fecha_inicio;
    protected $fecha_fin;
    protected $nombre;
    protected $tipo;
    protected $empresa;
    protected $tipo_fecha;
    protected $tipo_movimiento;

    function __construct($id, $fecha_inicio, $fecha_fin, $nombre, $tipo, $empresa, $tipo_fecha = 'aplicacion', $tipo_movimiento = 'TODOS') {
            $this->id = $id;
            $this->fecha_inicio = $fecha_inicio;
            $this->fecha_fin = $fecha_fin;
            $this->nombre = $nombre;
            $this->tipo = $tipo;
            $this->empresa = $empresa;
            $this->tipo_fecha = $tipo_fecha;
            $this->tipo_movimiento = $tipo_movimiento;
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
                ->setRGB('FF7401');//AZUL 

                $event->sheet->getDelegate()->getStyle('A2:D2')->getFont()->setSize(14);
                $event->sheet->getDelegate()->getStyle('A2:D2')->getFont()->setBold(true);
                $event->sheet->mergeCells('A2:D2');
                $event->sheet->getDelegate()->getStyle('A2:D2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FF7401');//AZUL 

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
                ->setRGB('FF7401');//NARANJA 

                //CONTADOR
                $contador = 0;
                if($this->tipo == "Cuentas"){
                    $varlista =  $this->ExcelHistorialCuentas($this->id, $this->fecha_inicio, $this->fecha_fin, $this->tipo_fecha, $this->tipo_movimiento);
                }else{
                    $varlista =  $this->ExcelHistorialCajas($this->id, $this->fecha_inicio, $this->fecha_fin, $this->tipo_fecha, $this->tipo_movimiento);
                }
                foreach ($varlista as $item){
                    $contador= $contador + 1;
                }
                $contador = $contador + 4;
                $celdas = "A3:M".$contador;

                //BORDER
                $event->sheet->getDelegate()->getStyle($celdas)
                ->getActiveSheet()
                ->getStyle($celdas)
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                $celdas = "F3:H".$contador;
                $event->sheet->getStyle($celdas)->getNumberFormat()->setFormatCode('$#,##0.00');

               
            },
        ];
    }


    public function collection()
    {
        if($this->tipo == "Cuentas"){
            $obtenerHistorialCuentas=  $this->ExcelHistorialCuentas($this->id, $this->fecha_inicio, $this->fecha_fin, $this->tipo_fecha, $this->tipo_movimiento);
            return $obtenerHistorialCuentas;
        }else{
            $obtenerHistorialCuentas=  $this->ExcelHistorialCajas($this->id, $this->fecha_inicio, $this->fecha_fin, $this->tipo_fecha, $this->tipo_movimiento);
            return $obtenerHistorialCuentas;
        }
    }

    public function headings(): array
    {
        if($this->tipo == "Cuentas"){
            $pertenece = "EMPRESA";
        }else{ $pertenece = "SUCURSAL";}
        
        return [
            ['REPORTE'],//$this->empresa
            ['ASIENTOS CONTABLES'],
            [
                'FECHA',
                $pertenece,
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
