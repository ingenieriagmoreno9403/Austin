<?php

namespace App\Imports;

use App\Models\NominaAsistencias;
use App\Models\Nominas_pagosenc;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Throwable;

class AsistenciasRelojImport implements ToCollection
{
    protected $id;

    protected $empleadosValidos;

    protected $checadasPorEmpleado = [];

    public function __construct(int $id)
    {
        $this->id = $id;

        try {
            $this->empleadosValidos = DB::table('tblnominas_pagodet')
                ->where('idpagonomina', $id)
                ->pluck('idempleado')
                ->map(fn ($empleadoId) => (int) $empleadoId)
                ->all();
        } catch (Throwable $e) {
            throw new \RuntimeException('No se pudieron obtener los empleados de la nómina: ' . $e->getMessage(), 0, $e);
        }
    }

    public function collection(Collection $rows)
    {
        try {
            if ($rows->isEmpty()) {
                throw new \RuntimeException('El archivo Excel no contiene filas de datos.');
            }

            $empleadoActual = null;
            $fila = 0;
            $empleadosEnArchivo = [];

            foreach ($rows as $row) {
                $fila++;
                $valores = $row->values()->all();
                $colA = trim((string) ($valores[0] ?? ''));

                if ($colA === '') {
                    continue;
                }

                if (str_starts_with($colA, 'Empleado:')) {
                    $empleadoActual = $this->extraerIdEmpleado($colA);

                    if ($empleadoActual === null) {
                        throw new \RuntimeException("No se pudo leer el ID del empleado en la fila {$fila}: {$colA}");
                    }

                    $empleadosEnArchivo[$empleadoActual] = true;

                    if (!isset($this->checadasPorEmpleado[$empleadoActual])) {
                        $this->checadasPorEmpleado[$empleadoActual] = [];
                    }

                    continue;
                }

                if (
                    str_starts_with($colA, 'Total de Asistencia')
                    || str_starts_with($colA, 'Fecha desde')
                    || $colA === 'Fecha'
                    || $colA === 'Totales'
                ) {
                    continue;
                }

                if ($empleadoActual === null) {
                    continue;
                }

                if (!in_array($empleadoActual, $this->empleadosValidos, true)) {
                    continue;
                }

                $fechaAsistencia = $this->normalizarFecha($colA);

                if ($fechaAsistencia === null) {
                    continue;
                }

                $horaEntrada = $this->normalizarHora($valores[7] ?? null, $fila, 'Entrada', false);
                $horaSalida = $this->normalizarHora($valores[8] ?? null, $fila, 'Salida', false);

                $this->checadasPorEmpleado[$empleadoActual][$fechaAsistencia] = [
                    'entrada' => $horaEntrada,
                    'salida' => $horaSalida,
                ];
            }

            if (empty($empleadosEnArchivo)) {
                throw new \RuntimeException('No se encontraron bloques de empleados en el archivo. Verifique que sea un reporte "Total de Asistencia".');
            }

            $coincidencias = array_intersect(array_keys($empleadosEnArchivo), $this->empleadosValidos);

            if (empty($coincidencias)) {
                throw new \RuntimeException(
                    'Ningún empleado del archivo pertenece a esta nómina. IDs en archivo: '
                    . implode(', ', array_keys($empleadosEnArchivo))
                );
            }

            $nominaEnc = Nominas_pagosenc::find($this->id);

            if (!$nominaEnc) {
                throw new \RuntimeException("No se encontró la nómina con ID {$this->id}.");
            }

            $usuario = auth()->user()->name ?? 'sistema';

            NominaAsistencias::procesarNominaDesdeImportacion(
                $this->id,
                $nominaEnc->fecha_inicio,
                $nominaEnc->fecha_fin,
                $this->checadasPorEmpleado,
                $usuario
            );
        } catch (Throwable $e) {
            throw new \RuntimeException('Error al procesar reporte de reloj checador: ' . $e->getMessage(), 0, $e);
        }
    }

    protected function extraerIdEmpleado(string $linea): ?int
    {
        if (!preg_match('/\[(\d+)\]/', $linea, $coincidencias)) {
            return null;
        }

        return (int) $coincidencias[1];
    }

    protected function normalizarFecha($valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        if (is_numeric($valor)) {
            return Carbon::createFromTimestampUTC(((float) $valor - 25569) * 86400)->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $valor)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function normalizarHora($valor, int $fila, string $columna, bool $lanzarError = true): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('H:i:s');
        }

        if (is_numeric($valor)) {
            $segundos = (int) round(((float) $valor - floor((float) $valor)) * 86400);
            return gmdate('H:i:s', $segundos);
        }

        $texto = trim((string) $valor);

        if ($texto === '00:00') {
            return null;
        }

        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $texto)) {
            return strlen($texto) === 5 ? $texto . ':00' : $texto;
        }

        try {
            return Carbon::parse($texto)->format('H:i:s');
        } catch (\Exception $e) {
            if ($lanzarError) {
                throw new \RuntimeException("En la fila {$fila}, la columna {$columna} tiene un formato inválido: '{$texto}'.");
            }

            return null;
        }
    }
}
