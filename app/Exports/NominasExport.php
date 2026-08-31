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
use Maatwebsite\Excel\Concerns\PHPExcel_Cell_DataType;
use DB;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class NominasExport implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents ,WithDrawings
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
            //TITULO
                $event->sheet->getDelegate()->getStyle('A1:W1')->getFont()->setSize(24);
                $event->sheet->getDelegate()->getStyle('A1:W1')->getFont()->setBold(true);
                $event->sheet->mergeCells('B1:C1');

                $event->sheet->getStyle('A1')->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
               

            //MERGES
                $event->sheet->getStyle('A2:WW2')->applyFromArray([
                    'font' => [
                        'name'  => 'Calibri',
                        'size'  => 14,
                        'bold'  => true,
                        'color' => ['argb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $celling = 'E2:G2';
                $event->sheet->mergeCells($celling);
                $event->sheet->getDelegate()->getStyle($celling)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('0F243E');//AZUL FUERTE
                
                $celling = 'I2:K2';
                $event->sheet->mergeCells($celling);
                $event->sheet->getDelegate()->getStyle($celling)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('366092');//AZUL MARINO


                $celling = 'M2:N2';
                $event->sheet->mergeCells($celling);
                $event->sheet->getDelegate()->getStyle($celling)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('963634');//ROJO

                $celling = 'O2:R2';
                $event->sheet->mergeCells($celling);
                $event->sheet->getDelegate()->getStyle($celling)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('9379AF');//MORADO

                $celling = 'S2:T2';
                $event->sheet->mergeCells($celling);
                $event->sheet->getDelegate()->getStyle($celling)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('00B0F0');//AZUL

                $celling = 'U2:V2';
                $event->sheet->mergeCells($celling);
                $event->sheet->getDelegate()->getStyle($celling)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('366092');//AZUL MARINO

                $celling = 'AG2:AJ2';                
                $event->sheet->mergeCells($celling);
                $event->sheet->getDelegate()->getStyle($celling)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('60497A');//MORADO
        
                $celling = 'AK2:AN2';
                $event->sheet->mergeCells($celling);
                $event->sheet->getDelegate()->getStyle($celling)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('215967');//VERDE MARINO

                $celling = 'AO2:AQ2';
                $event->sheet->mergeCells($celling);
                $event->sheet->getDelegate()->getStyle($celling)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('31A547');//VERDE    


            //NOMBRES COLUMNAS
                $event->sheet->getStyle('A3:AS3')->applyFromArray([
                    'font' => [
                            'name'      =>  'Calibri',
                            'size'      =>  12,
                            'bold'      =>  true,
                            'color' => ['argb' => 'F2F2F2'],
                        ],
                ]);

                $event->sheet->getDelegate()->setAutoFilter('A3:AS3');
                $event->sheet->getStyle('A3:AS3')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff000000'],
                        ],
                    ]
                ]);

            //CONTADOR
                $contador = 0;
                $varlistanomina=  $this-> obtenernominasporidexport($this->id);
                foreach ($varlistanomina as $item){
                    $contador= $contador + 1;
                }
                $contador = $contador + 3;
                $celdas = "A4:AS".$contador;

            //BORDER GENERAL
                $event->sheet->getDelegate()->getStyle($celdas)
                ->getActiveSheet()
                ->getStyle($celdas)
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            //FORMATO DE NUMERO
                $celdas = "E4:G".$contador;
                $event->sheet->getStyle($celdas)->getNumberFormat()->setFormatCode('$#,##0.00');

                $celdas1 = "I4:K".$contador;
                $event->sheet->getStyle($celdas1)->getNumberFormat()->setFormatCode('$#,##0.00');

                $celdas2 = "N4:N".$contador;
                $event->sheet->getStyle($celdas2)->getNumberFormat()->setFormatCode('$#,##0.00');

                $celdas2 = "P4:R".$contador;
                $event->sheet->getStyle($celdas2)->getNumberFormat()->setFormatCode('$#,##0.00');

                $celdas2 = "T4:T".$contador;
                $event->sheet->getStyle($celdas2)->getNumberFormat()->setFormatCode('$#,##0.00');

                $celdas3 = "V4:AF".$contador;
                $event->sheet->getStyle($celdas3)->getNumberFormat()->setFormatCode('$#,##0.00');

                $celdas3 = "AH4:AJ".$contador;
                $event->sheet->getStyle($celdas3)->getNumberFormat()->setFormatCode('$#,##0.00');

                $celdas3 = "AL4:AQ".$contador;
                $event->sheet->getStyle($celdas3)->getNumberFormat()->setFormatCode('$#,##0.00');
              
            //ESTILO A CELDAS
            $event->sheet->getDelegate()->getStyle('A3:G3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('16365C');//AZUL MARINO
               
            $event->sheet->getDelegate()->getStyle('A3:G3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('16365C');//AZUL MARINO
                    $celdas = "A4:G".$contador;
                    $event->sheet->getDelegate()->getStyle($celdas)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('DCE6F1');//AZUL LIGHT


            $event->sheet->getDelegate()->getStyle('H3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('404040');//GRIS
                    $celdas = "H4:H".$contador;
                    $event->sheet->getDelegate()->getStyle($celdas)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F2F2F2');//GRIS LIGHT

            $event->sheet->getDelegate()->getStyle('I3:J3')
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('366092');//AZUL OSCURO
                        $celdas = "I4:J".$contador;
                        $event->sheet->getDelegate()->getStyle($celdas)
                        ->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('DCE6F1');//AZUL LIGHT
             

            $event->sheet->getDelegate()->getStyle('K3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('16365C');//AZUL OSCURO
                    $celdas = "K4:K".$contador;
                    $event->sheet->getDelegate()->getStyle($celdas)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('C5D9F1');//AZUL LIGHT
            
            $event->sheet->getDelegate()->getStyle('L3')
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('E26B0A');//NARANJA
                $celdas = "L4:L".$contador;
                $event->sheet->getDelegate()->getStyle($celdas)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FDE9D9');//ROJO LIGHT

      
            $event->sheet->getDelegate()->getStyle('M3')
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('AB1515');//ROJO VINO
                $celdas = "M4:M".$contador;
                $event->sheet->getDelegate()->getStyle($celdas)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('F2DCDB');//ROJO LIGHT

            $event->sheet->getDelegate()->getStyle('N3')
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('963634');//ROJO VINO
                $celdas = "N4:N".$contador;
                $event->sheet->getDelegate()->getStyle($celdas)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('E6B8B7');//ROJO LIGHT

            $event->sheet->getDelegate()->getStyle('O3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('8064A2');//MORADO 
                    $celdas = "O4:O".$contador;
                    $event->sheet->getDelegate()->getStyle($celdas)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('E4DFEC');//MORADO LIGHT

            $event->sheet->getDelegate()->getStyle('P3:Q3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('60497A');//MORADO 
                    $celdas = "P4:Q".$contador;
                    $event->sheet->getDelegate()->getStyle($celdas)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('CCC0DA');//MORADO LIGHT

            $event->sheet->getDelegate()->getStyle('R3')
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('403151');//MORADO 
                        $celdas = "R4:R".$contador;
                        $event->sheet->getDelegate()->getStyle($celdas)
                        ->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('B1A0C7');//MORADO LIGHT


            $event->sheet->getDelegate()->getStyle('S3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('00A1DA');//AZUL 
                    $celdas = "S4:S".$contador;
                    $event->sheet->getDelegate()->getStyle($celdas)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('DCE6F1');//AZUL LIGHT
            

            $event->sheet->getDelegate()->getStyle('T3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('007FAC');//AZUL 
                $celdas = "T4:T".$contador;
                $event->sheet->getDelegate()->getStyle($celdas)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('C5D9F1');//AZUL LIGHT


            $event->sheet->getDelegate()->getStyle('U3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('366092');//AZUL 
                    $celdas = "U4:U".$contador;
                    $event->sheet->getDelegate()->getStyle($celdas)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('DCE6F1');//AZUL LIGHT
            

            $event->sheet->getDelegate()->getStyle('V3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('16365C');//AZUL 
                $celdas = "V4:V".$contador;
                $event->sheet->getDelegate()->getStyle($celdas)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('C5D9F1');//AZUL LIGHT
           
             $event->sheet->getDelegate()->getStyle('W3:AA3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('31869B');//AZUL
                    $celdas = "W4:AA".$contador;
                    $event->sheet->getDelegate()->getStyle($celdas)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('DAEEF3');//AZUL LIGHT

                $event->sheet->getDelegate()->getStyle('AB3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('963634');//GUINDA
                    $celdas = "AB4:AB".$contador;
                    $event->sheet->getDelegate()->getStyle($celdas)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F2DCDB');//GUINDA LIGHT

                $event->sheet->getDelegate()->getStyle('AC3:AF3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('E26B0A');//NARANJA TOSTADO
                    $celdas = "AC4:AF".$contador;
                    $event->sheet->getDelegate()->getStyle($celdas)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('FDE9D9');//NARANJA LIGHT

                $event->sheet->getDelegate()->getStyle('AG3')
                ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('60497A');//MORADO 
                        $celdas = "AG4:AG".$contador;
                        $event->sheet->getDelegate()->getStyle($celdas)
                        ->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('CCC0DA');//MORADO LIGHT

                $event->sheet->getDelegate()->getStyle('AH3:AI3')
                ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('B1A0C7');//PURPLE 
                        $celdas = "AH4:AI".$contador;
                        $event->sheet->getDelegate()->getStyle($celdas)
                        ->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('E4DFEC');//PURPLE LIGHT

                $event->sheet->getDelegate()->getStyle('AJ3')
                ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('60497A');//MORADO 
                        $celdas = "AJ4:AJ".$contador;
                        $event->sheet->getDelegate()->getStyle($celdas)
                        ->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('CCC0DA');//MORADO LIGHT

                 $event->sheet->getDelegate()->getStyle('AK3')
                ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('215967');//ACUA 
                        $celdas = "AK4:AK".$contador;
                        $event->sheet->getDelegate()->getStyle($celdas)
                        ->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('B8CEDA');//ACUA LIGHT
                
                $event->sheet->getDelegate()->getStyle('AL3:AM3')
                ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('31869B');//ACUA 
                        $celdas = "AL4:AM".$contador;
                        $event->sheet->getDelegate()->getStyle($celdas)
                        ->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('DAEEF3');//ACUA LIGHT

                 $event->sheet->getDelegate()->getStyle('AN3')
                ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('215967');//ACUA 
                        $celdas = "AN4:AN".$contador;
                        $event->sheet->getDelegate()->getStyle($celdas)
                        ->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('B8CEDA');//ACUA LIGHT


                $event->sheet->getDelegate()->getStyle('AO3:AP3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('366092');//AZUL MARINO
                    $celdas = "AO4:AP".$contador;
                    $event->sheet->getDelegate()->getStyle($celdas)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('DCE6F1');//AZUL LIGHT

                $event->sheet->getDelegate()->getStyle('AQ3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('009900');//VERDE
                    $celdas = "AQ4:AQ".$contador;
                    $event->sheet->getDelegate()->getStyle($celdas)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('EBF1DE');//VERDE LIGHT

            

                $event->sheet->getDelegate()->getStyle('AR3:AS3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('404040');//GRIS OBSCURO
                    $celdas = "AR4:AS".$contador;
                    $event->sheet->getDelegate()->getStyle($celdas)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('D9D9D9');//GRIS LIGHT

                
            },
        ];
    }


    public function collection()
    {
        $varlistanomina=  $this-> obtenernominasporidexport($this->id);
        return $varlistanomina;
    }

    public function drawings() {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('This is my logo');
        $drawing->setPath(public_path('/Images/IOHISA-blanco-vertical.png'));
        $drawing->setHeight(45);
        $drawing->setCoordinates('A1');

        return [$drawing];
    }
    

    public function headings(): array
    {
        return [
            [' ','NOMINA DE EMPLEADOS'],
            [   ' ',' ',' ',' ',
                'SALARIO',' ',' ',' ',
                'SUELDO',' ',' ',' ',
                'FALTAS/RET/AUS',' ',
                'HORAS EXTRAS',' ',' ',' ',
                'DÍAS DE DESCANSO',' ',
                'PRIMA DOMINICAL',' ',
                ' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',
                'DIAS DE VACACIONES',' ',' ',' ',
                'PRIMA VACACIONAL',' ',' ',' ',
                'TOTALES',' ',' ',' ',' ',
            ],
            [
                'SUCURSAL',
                'PUESTO',
                'NO. EMPLEADO',
                'NOMBRE COMPLETO',
                'SD',
                'SDI',
                'SDE',

                'DIAS LABORADOS', 
                'FISCAL',  
                'EXCEDENTE',
                'POR DIAS LABORADOS',

                'DIAS DE INCAPACIDAD',
                'DIAS',
                'DESCUENTO TOTAL',  

                'HORAS',
                'PAGO FISCAL',
                'PAGO EXCEDENTE',
                'PAGO TOTAL',

                'DÍAS',
                'PAGO TOTAL',
                'DÍAS',
                'PAGO TOTAL',

                'INFONAVIT',
                'IMSS',
                'SUBSIDIO',
                'ISR',
                'FONACOT',
                
                'PRESTAMO EMPRESARIAL',
     
                'BONO',
                'OTROS',
                'DESPENSA',
                'PERCEPCION EXENTA',

                'DÍAS',
                'PAGO FISCAL',
                'PAGO EXCEDENTE',
                'PAGO TOTAL',

                'DÍAS',
                'PAGO FISCAL',
                'PAGO EXCEDENTE',
                'PAGO TOTAL',

                'NOMINA FISCAL',
                'NOMINA EXCEDENTE',
                'TOTAL A PAGAR',

                'BANCO',
                'NUMERO CUENTA'   
                
                
            ]
        ];
    }

   
}
