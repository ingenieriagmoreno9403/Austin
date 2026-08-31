<?php

namespace App\Exports;

use App\Models\cuentas;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\DatosimpleTraits;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;
use DB;

class CierresExport implements   FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    protected $fecha;

    function __construct($fecha) {
        $this->fecha = $fecha;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
   
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {

     
                $event->sheet->getDelegate()->getStyle('A1:F1')->getFont()->setSize(14);
                $event->sheet->getDelegate()->getStyle('A2:AB2')->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle('A1:AB2')->getFont()->setBold(true);
                $event->sheet->mergeCells('A1:E1');
                $event->sheet->getDelegate()->getStyle('A1:F1')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ff00b0f0');//AZUL 

                $event->sheet->getDelegate()->getStyle('A2:AB2')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('ff00b0f0');//AZUL 

                $event->sheet->getDelegate()->setAutoFilter('A2:AB2');
                $event->sheet->getStyle('A2:AB2')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'ff000000'],
                        ],
                    ]
                ]);



      
                $event->sheet->getDelegate()->getStyle('A1:AB2')->getFont()->getColor()
                ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
                        
            },
        ];
    }


    public function collection()
    {
        $var = DB::select("select 
        numero,
        sucursal,
        numero_cor,
        nombre_cor,
        numero_contrato,
        iddistribuidor,
        distribuidor,
        fecha_activacion,
        estado,
        FORMAT(capitalautorizado,2),
        FORMAT(capital,2),
        FORMAT(intereses,2),
        FORMAT(ivaintereses,2),
        FORMAT(cobertura,2),
        FORMAT(ivacobertura,2),
        FORMAT(otros,2),
        diasatraso,
        FORMAT(saldo_riesgo,2),
        FORMAT(saldo_atrasado,2),
        FORMAT(saldo,2),
        FORMAT(capitalpagado,2),
        clientes_vigentes,
        FORMAT(capital_desembolsado,2),
        fecha_corte, 
        FORMAT(pago_alcorte,2),
        FORMAT(abonado,2),
        coord_anterior,
        fecha_reasignacion
        from tblcierres where fecha_corte = ?",[$this->fecha]);
        $datoscierre = collect($var);
        return $datoscierre;
    }

    public function headings(): array
    {
        return [
            ['REPORTE DE CIERRE'],
            [
                "ID SUCURSAL",
                "SUCURSAL",
                "ID COORDINADOR",
                "NOMBRE COORDINADOR",	
                "NO. CONTRATO",	
                "ID DV",	
                "NOMBRE DISTRIBUIDOR",
                "FECHA ACTIVACIÓN",
                "ESTADO ACTUAL",
                "CAPTAL AUTORIZADO",
                "CAPITAL ACTIVO",
                "INTERESES",
                "IVA INTERESES",
                "COBERTURA",
                "IVA COBERTUA",
                "OTROS",
                "DIAS DE ATRASO",
                "SALDO EN RIESGO",
                "SALDO ATRASADO",
                "SALDO",
                "CAPITAL PAGADO",
                "CLIENTES VIGENTES",
                "CAPITAL DESEMBOLSADO",
                "FECHA DE CORTE",
                "PAGO AL CORTE",
                "ABONADO",
                "COORDINADOR ANTERIOR",
                "FECHA DE REASIGNACION",
            ]
        ];
    }

   
}
