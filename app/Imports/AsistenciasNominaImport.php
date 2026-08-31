<?php

namespace App\Imports;

use App\Models\NominaAsistencias;
use App\Models\Nominas_pagosenc;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class AsistenciasNominaImport implements ToCollection, WithHeadingRow
{
    protected $id;

    protected $empleadosValidos;

    protected $checadasPorEmpleado = [];

    public function __construct($id)
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

            $fila = 1;

            foreach ($rows as $row) {
                $fila++;

                $idEmpleado = (int) ($row['id_empleado'] ?? 0);
                $fechaAsistencia = $this->normalizarFecha($row['fecha'] ?? null);

                if ($idEmpleado <= 0 || !$fechaAsistencia) {
                    continue;
                }

                if (!in_array($idEmpleado, $this->empleadosValidos, true)) {
                    throw new \RuntimeException("La fila {$fila} contiene el empleado ID {$idEmpleado}, que no pertenece a esta nómina.");
                }

                $horaEntrada = $this->normalizarHora($row['hora_entrada'] ?? null, $fila);
                $horaSalida = $this->normalizarHora($row['hora_salida'] ?? null, $fila);

                if (!isset($this->checadasPorEmpleado[$idEmpleado])) {
                    $this->checadasPorEmpleado[$idEmpleado] = [];
                }

                $this->checadasPorEmpleado[$idEmpleado][$fechaAsistencia] = [
                    'entrada' => $horaEntrada,
                    'salida' => $horaSalida,
                ];
            }

            if (empty($this->checadasPorEmpleado)) {
                throw new \RuntimeException('No se encontraron filas válidas en la plantilla de asistencias.');
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
            throw new \RuntimeException('Error al procesar el archivo de asistencias: ' . $e->getMessage(), 0, $e);
        }
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

    protected function normalizarHora($valor, int $fila = 0): ?string
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

        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $texto)) {
            return strlen($texto) === 5 ? $texto . ':00' : $texto;
        }

        try {
            return Carbon::parse($texto)->format('H:i:s');
        } catch (\Exception $e) {
            $prefijo = $fila > 0 ? "En la fila {$fila}, " : '';
            throw new \RuntimeException("{$prefijo}el formato de hora '{$texto}' no es válido. Use formato 24 horas, por ejemplo 08:00 o 17:30.");
        }
    }
}
