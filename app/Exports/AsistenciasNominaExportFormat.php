<?php



namespace App\Exports;



use App\Models\Nominas_pagosenc;

use App\Models\NominaAsistencias;

use App\Services\AsistenciasCalculoService;

use App\Traits\DatosimpleTraits;

use Carbon\Carbon;

use Maatwebsite\Excel\Concerns\Exportable;

use Maatwebsite\Excel\Concerns\FromCollection;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;

use Maatwebsite\Excel\Concerns\WithEvents;

use Maatwebsite\Excel\Concerns\WithHeadings;

use Maatwebsite\Excel\Events\AfterSheet;

use Throwable;



class AsistenciasNominaExportFormat implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents

{

    use DatosimpleTraits;

    use Exportable;



    protected $id;



    protected $totalFilas = 0;



    public function __construct($id)

    {

        $this->id = $id;

    }



    public function registerEvents(): array

    {

        return [

            AfterSheet::class => function (AfterSheet $event) {

                $contador = max($this->totalFilas + 1, 1);

                $cellRange = 'A1:J1';

                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);

                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);

                $event->sheet->getDelegate()->setAutoFilter($cellRange);



                $event->sheet->getStyle($cellRange)->applyFromArray([

                    'borders' => [

                        'outline' => [

                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,

                            'color' => ['argb' => '000000'],

                        ],

                    ],

                ]);



                $event->sheet->getDelegate()->getStyle('A1:B1')

                    ->getFill()

                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)

                    ->getStartColor()

                    ->setRGB('ffc000');



                $event->sheet->getDelegate()->getStyle('C1:E1')

                    ->getFill()

                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)

                    ->getStartColor()

                    ->setRGB('C4D79B');



                $event->sheet->getDelegate()->getStyle('F1:G1')

                    ->getFill()

                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)

                    ->getStartColor()

                    ->setRGB('92CDDC');



                $event->sheet->getDelegate()->getStyle('H1:J1')

                    ->getFill()

                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)

                    ->getStartColor()

                    ->setRGB('E4DFEC');



                $celdas = 'A1:J' . $contador;

                $event->sheet->getDelegate()->getStyle($celdas)

                    ->getBorders()

                    ->getAllBorders()

                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            },

        ];

    }



    public function collection()

    {

        try {

            $nominaEnc = Nominas_pagosenc::find($this->id);



            if (!$nominaEnc) {

                throw new \RuntimeException("No se encontró la nómina con ID {$this->id}.");

            }



            if (empty($nominaEnc->fecha_inicio) || empty($nominaEnc->fecha_fin)) {

                throw new \RuntimeException("La nómina {$this->id} no tiene fechas de inicio y fin configuradas.");

            }



            $empleados = $this->obtenerformatnominasporidexport($this->id);



            if ($empleados->isEmpty()) {

                throw new \RuntimeException("La nómina {$this->id} no tiene empleados para generar el formato.");

            }



            $asistencias = NominaAsistencias::obtenerPorNomina(

                $this->id,

                $nominaEnc->fecha_inicio,

                $nominaEnc->fecha_fin

            )->keyBy(fn ($row) => $row->id_empleado . '_' . $row->fecha);



            $inicio = Carbon::parse($nominaEnc->fecha_inicio);

            $fin = Carbon::parse($nominaEnc->fecha_fin);



            if ($inicio->gt($fin)) {

                throw new \RuntimeException("La fecha inicio ({$nominaEnc->fecha_inicio}) es mayor que la fecha fin ({$nominaEnc->fecha_fin}).");

            }



            AsistenciasCalculoService::limpiarCache();



            $rows = collect();

            $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];



            foreach ($empleados as $empleado) {

                $fechaActual = $inicio->copy();



                while ($fechaActual->lte($fin)) {

                    $fecha = $fechaActual->format('Y-m-d');

                    $key = $empleado->id_empleado . '_' . $fecha;

                    $existente = $asistencias->get($key);

                    $entrada = $existente?->entrada ?: null;

                    $salida = $existente?->salida ?: null;



                    $idHorarioOverride = !empty($existente?->id_horario) ? (int) $existente->id_horario : null;

                    $horarioDia = AsistenciasCalculoService::resolverHorario(
                        $idHorarioOverride,
                        (int) $empleado->id_empleado,
                        $fecha
                    );

                    $calculo = AsistenciasCalculoService::calcularDia(
                        (int) $empleado->id_empleado,
                        $fecha,
                        $entrada,
                        $salida,
                        $idHorarioOverride
                    );

                    $rows->push([
                        'id_empleado' => $empleado->id_empleado,
                        'nombre_empleado' => $empleado->Nombre_Empleado,
                        'fecha' => $fecha,
                        'dia_semana' => $diasSemana[$fechaActual->dayOfWeek],
                        'horario' => AsistenciasCalculoService::formatearHorario($horarioDia),

                        'hora_entrada' => $entrada ? substr($entrada, 0, 5) : '',

                        'hora_salida' => $salida ? substr($salida, 0, 5) : '',

                        'estado' => $calculo['estado'],

                        'dia_trabajado' => $calculo['dia_trabajo'],

                        'comentario' => $calculo['comentario'],

                    ]);



                    $fechaActual->addDay();

                }

            }



            $this->totalFilas = $rows->count();



            return $rows;

        } catch (Throwable $e) {

            throw new \RuntimeException('No se pudo generar el formato de asistencias: ' . $e->getMessage(), 0, $e);

        }

    }



    public function headings(): array

    {

        return [

            'ID_EMPLEADO',

            'NOMBRE_EMPLEADO',

            'FECHA',

            'DIA_SEMANA',

            'HORARIO',

            'HORA_ENTRADA',

            'HORA_SALIDA',

            'ESTADO',

            'DIA_TRABAJADO',

            'COMENTARIO',

        ];

    }

}


