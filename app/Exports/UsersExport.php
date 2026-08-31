<?php

namespace App\Exports;

use App\Models\Empleados;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\Menutrait;
use App\Traits\DatosimpleTraits;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class UsersExport implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    use MenuTrait;
    use DatosimpleTraits;
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */
   
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {


                $cellRange = 'A1:W1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
                $event->sheet->getDelegate()->getStyle('A2:AT2')->getFont()->setBold(true);
                $event->sheet->getDelegate()->setAutoFilter('A2:AT2');
                $event->sheet->getStyle('A2:AT2')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff404040'],
                        ],
                    ]
                ]);
                // $ev
                // $event->$shet->setAutoFilter('A2');


                $event->sheet->getDelegate()->getStyle('A2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('ffd9d9d9');

                $event->sheet->getDelegate()->getStyle('B2:C2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('ffc6e0b4');

                $event->sheet->getDelegate()->getStyle('D2:AC2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('ffb8cce4');

                $event->sheet->getDelegate()->getStyle('AD2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('ffc6e0b4');

             

                $event->sheet->getDelegate()->getStyle('AE2:AF2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('ffccc0da');
            

                $event->sheet->getDelegate()->getStyle('AG2:AH2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('ffb7dee8');


                $event->sheet->getDelegate()->getStyle('AI2:AK2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('ffc6e0b4');

                $event->sheet->getDelegate()->getStyle('AL2:AO2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('ffb8cce4');

                $event->sheet->getDelegate()->getStyle('AP2:AR2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('fffabf8f');

                $event->sheet->getDelegate()->getStyle('AS2:AT2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('ffb7dee8');
            },
        ];
    }


    public function collection()
    {
        $varlistaempleados=  $this-> extraerempleados();
        return $varlistaempleados;
    }
    

    public function headings(): array
    {
        return [
            ['Catalogo de Empleadaos'],
            [
                'No. Empleado',
                'Empresa',
                'Sucursal',          
                'Apellido Paterno',
                'Apellido Materno',
                'Primer Nombre',
                'Segundo Nombre',
                'Telefono.',
                'Correo',
                'Nacionalidad',
                'Fecha Nacimiento',
                'Grado de estudio',
                'Puesto',
                'RFC',
                'CURP',
                'NSS',
                'Sexo',
                'Tipo Sangre',
                'Estado Civil',
                'Calle',
                'Colonia',
                'No. Interior',
                'No. Exterior',
                'C.P.',
                'Ciudad',
                'Fecha Alta',
                'Fecha Baja', 
                'Estado',
                'Descripcion de estado',  
                'ID Nomina',
                'Fecha de Ingreso IMSS',  
                'Pago IMSS', 
                'Salario Mensual',     
                'Salario Fijo',
                'Salario Fiscal',
                'Excedente',
                'Efectivo',  
                'Tipo Infonavit',  
                'Factor SUA',
                'Descuento Quincenal',
                'No. Credito Infonavit',   
                'Banco',
                'No. Tarjeta',
                'No. Cuenta',     
                'Contato Emergencia',
                'Telefono Emergencia',
                
                
                
            ]
        ];
    }

   
}
