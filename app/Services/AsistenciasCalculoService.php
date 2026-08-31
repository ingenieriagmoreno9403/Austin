<?php

namespace App\Services;

use App\Models\Horarios;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AsistenciasCalculoService
{
    protected static array $horariosCache = [];

    protected static array $horariosEmpleadoCache = [];

    public static function calcularDia(
        int $idEmpleado,
        string $fecha,
        ?string $entrada,
        ?string $salida,
        ?int $idHorarioOverride = null
    ): array {
        $horarioDia = self::resolverHorario($idHorarioOverride, $idEmpleado, $fecha);

        if ($horarioDia === null) {
            return [
                'estado' => 'F',
                'dia_trabajo' => 0,
                'comentario' => 'Sin horario asignado para este día',
            ];
        }

        if (self::esDiaDescanso($horarioDia)) {
            return [
                'estado' => 'A',
                'dia_trabajo' => 1,
                'comentario' => 'Día de descanso (1 día pagado)',
            ];
        }

        $minutosProgramados = self::minutosEntreTiempos($horarioDia->entrada, $horarioDia->salida, $horarioDia->tipo);

        if ($minutosProgramados <= 0) {
            return [
                'estado' => 'A',
                'dia_trabajo' => 0,
                'comentario' => 'Día sin jornada laboral',
            ];
        }

        if ($entrada === null && $salida === null) {
            return [
                'estado' => 'F',
                'dia_trabajo' => 0,
                'comentario' => 'Falta: sin registro de entrada ni salida',
            ];
        }

        if ($entrada === null || $salida === null) {
            return [
                'estado' => 'F',
                'dia_trabajo' => 0,
                'comentario' => 'Falta: registro incompleto de checada',
            ];
        }

        $minutosTrabajados = self::minutosEntreTiempos($entrada, $salida);

        if ($minutosTrabajados <= 0) {
            return [
                'estado' => 'F',
                'dia_trabajo' => 0,
                'comentario' => 'Falta: horas trabajadas no válidas',
            ];
        }

        $proporcion = $minutosTrabajados / $minutosProgramados;
        $diaTrabajo = $proporcion >= 1
            ? 1
            : round($proporcion, 2);

        if ($diaTrabajo <= 0) {
            return [
                'estado' => 'F',
                'dia_trabajo' => 0,
                'comentario' => 'Falta: no alcanzó el mínimo de la jornada',
            ];
        }

        $comentario = $diaTrabajo >= 1
            ? 'Asistencia completa'
            : sprintf(
                'Asistencia parcial: %.2f día (%.0f min de %.0f min programados)',
                $diaTrabajo,
                $minutosTrabajados,
                $minutosProgramados
            );

        return [
            'estado' => 'A',
            'dia_trabajo' => $diaTrabajo,
            'comentario' => $comentario,
        ];
    }

    public static function resolverHorario(?int $idHorarioOverride, int $idEmpleado, string $fecha): ?object
    {
        if ($idHorarioOverride !== null && $idHorarioOverride > 0) {
            return self::obtenerHorario($idHorarioOverride);
        }

        return self::obtenerHorarioEmpleadoDia($idEmpleado, $fecha);
    }

    public static function formatearHorario(?object $horario): string
    {
        if ($horario === null) {
            return 'Sin horario';
        }

        $tipo = (string) ($horario->tipo ?? '');

        if (self::esDiaDescanso($horario) || empty($horario->entrada) || empty($horario->salida)) {
            return $tipo !== '' ? $tipo : 'Sin horario';
        }

        return sprintf(
            '%s (%s - %s)',
            $tipo,
            substr((string) $horario->entrada, 0, 5),
            substr((string) $horario->salida, 0, 5)
        );
    }

    public static function obtenerHorarioEmpleadoDia(int $idEmpleado, string $fecha): ?object
    {
        $idDia = self::idDiaDesdeFecha($fecha);
        $horariosEmpleado = self::obtenerHorariosEmpleado($idEmpleado);
        $asignacion = $horariosEmpleado->get($idDia);

        if (!$asignacion || empty($asignacion->id_horario)) {
            return null;
        }

        return self::obtenerHorario((int) $asignacion->id_horario);
    }

    public static function idDiaDesdeFecha(string $fecha): int
    {
        return Carbon::parse($fecha)->dayOfWeek + 1;
    }

    public static function esDiaDescanso(object $horario): bool
    {
        $tipo = strtoupper((string) ($horario->tipo ?? ''));

        return str_contains($tipo, 'DESCANSO');
    }

    public static function minutosEntreTiempos(
        ?string $entrada,
        ?string $salida,
        ?string $tipoHorario = null
    ): int {
        if (!$entrada || !$salida) {
            return 0;
        }

        $inicio = self::crearFechaDesdeTiempo($entrada, $tipoHorario);
        $fin = self::crearFechaDesdeTiempo($salida, $tipoHorario);

        if ($fin->lte($inicio)) {
            $fin->addDay();
        }

        return $inicio->diffInMinutes($fin);
    }

    protected static function crearFechaDesdeTiempo(string $tiempo, ?string $tipoHorario = null): Carbon
    {
        $partes = explode(':', $tiempo);
        $hora = (int) ($partes[0] ?? 0);
        $minuto = (int) ($partes[1] ?? 0);
        $segundo = (int) ($partes[2] ?? 0);

        if ($hora === 0 && $minuto === 0 && $segundo > 0 && $segundo <= 23) {
            return Carbon::createFromTime($segundo, 0, 0);
        }

        return Carbon::createFromTime($hora, $minuto, $segundo);
    }

    protected static function obtenerHorariosEmpleado(int $idEmpleado)
    {
        if (!isset(self::$horariosEmpleadoCache[$idEmpleado])) {
            self::$horariosEmpleadoCache[$idEmpleado] = DB::table('tblempleados_horarios')
                ->where('id_empleado', $idEmpleado)
                ->get()
                ->keyBy(fn ($row) => (int) $row->id_dia);
        }

        return self::$horariosEmpleadoCache[$idEmpleado];
    }

    protected static function obtenerHorario(int $idHorario): ?object
    {
        if (!isset(self::$horariosCache[$idHorario])) {
            self::$horariosCache[$idHorario] = Horarios::find($idHorario);
        }

        return self::$horariosCache[$idHorario];
    }

    public static function limpiarCache(): void
    {
        self::$horariosCache = [];
        self::$horariosEmpleadoCache = [];
    }
}
