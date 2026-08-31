<?php

namespace App\Models;

use App\Services\AsistenciasCalculoService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NominaAsistencias extends Model
{
    use HasFactory;

    public $table = 'tblnomina_asistencias';

    protected $fillable = [
        'idpagonomina',
        'id_empleado',
        'fecha',
        'entrada',
        'salida',
        'id_horario',
        'estado',
        'dia_trabajo',
        'comentario',
        'created_by',
        'updated_by',
    ];

    public static function usaIdPagoNomina(): bool
    {
        return Schema::hasTable('tblnomina_asistencias')
            && Schema::hasColumn('tblnomina_asistencias', 'idpagonomina');
    }

    public static function obtenerPorNomina(int $idPagoNomina, string $fechaInicio, string $fechaFin)
    {
        $empleadoIds = DB::table('tblnominas_pagodet')
            ->where('idpagonomina', $idPagoNomina)
            ->pluck('idempleado');

        $query = DB::table('tblnomina_asistencias')
            ->whereIn('id_empleado', $empleadoIds)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if (self::usaIdPagoNomina()) {
            $query->where('idpagonomina', $idPagoNomina);
        }

        return $query->get();
    }

    public static function obtenerDetalleNomina(int $idPagoNomina, string $fechaInicio, string $fechaFin)
    {
        $query = DB::table('tblnomina_asistencias as a')
            ->join('tblempleados as e', 'e.id', '=', 'a.id_empleado')
            ->join('tblnominas_pagodet as d', function ($join) use ($idPagoNomina) {
                $join->on('d.idempleado', '=', 'a.id_empleado')
                    ->where('d.idpagonomina', '=', $idPagoNomina);
            })
            ->leftJoin('tblempleados_horarios as eh', function ($join) {
                $join->on('eh.id_empleado', '=', 'a.id_empleado')
                    ->whereRaw('eh.id_dia = DAYOFWEEK(a.fecha)');
            })
            ->leftJoin('tblhorarios as ho', 'ho.id', '=', 'a.id_horario')
            ->leftJoin('tblhorarios as hd', 'hd.id', '=', 'eh.id_horario')
            ->whereBetween('a.fecha', [$fechaInicio, $fechaFin])
            ->select(
                'a.id',
                'a.id_empleado',
                'a.fecha',
                'a.entrada',
                'a.salida',
                'a.id_horario',
                'a.estado',
                'a.dia_trabajo',
                'a.comentario',
                'eh.id_horario as horario_empleado_id',
                DB::raw('COALESCE(a.id_horario, eh.id_horario) as horario_id_efectivo'),
                DB::raw('COALESCE(ho.tipo, hd.tipo) as horario_tipo'),
                DB::raw('COALESCE(ho.entrada, hd.entrada) as horario_entrada'),
                DB::raw('COALESCE(ho.salida, hd.salida) as horario_salida'),
                DB::raw("TRIM(CONCAT(
                    e.primer_nombre, ' ',
                    IFNULL(NULLIF(e.segundo_nombre, ''), ''),
                    e.apellido_paterno, ' ',
                    e.apellido_materno
                )) as nombre_empleado")
            )
            ->orderBy('a.id_empleado')
            ->orderBy('a.fecha');

        if (self::usaIdPagoNomina()) {
            $query->where('a.idpagonomina', $idPagoNomina);
        }

        return $query->get();
    }

    public static function obtenerResumenPorEmpleado(int $idPagoNomina, string $fechaInicio, string $fechaFin)
    {
        $detalle = self::obtenerDetalleNomina($idPagoNomina, $fechaInicio, $fechaFin);

        return $detalle
            ->groupBy('id_empleado')
            ->map(function ($registros, $idEmpleado) {
                $coleccion = collect($registros);
                $primero = $coleccion->first();

                return (object) [
                    'id_empleado' => (int) $idEmpleado,
                    'nombre_empleado' => $primero->nombre_empleado,
                    'total_registros' => $coleccion->count(),
                    'dias_trabajados' => round((float) $coleccion->sum('dia_trabajo'), 2),
                    'faltas' => $coleccion->where('estado', 'F')->count(),
                    'asistencias' => $coleccion->where('estado', 'A')->count(),
                ];
            })
            ->sortBy('nombre_empleado')
            ->values();
    }

    public static function obtenerEmpleadosFiltroNomina(int $idPagoNomina, string $fechaInicio, string $fechaFin)
    {
        return self::obtenerDetalleNomina($idPagoNomina, $fechaInicio, $fechaFin)
            ->unique('id_empleado')
            ->sortBy('id_empleado')
            ->map(function ($row) {
                return (object) [
                    'id' => (int) $row->id_empleado,
                    'nombre' => $row->nombre_empleado,
                ];
            })
            ->values();
    }

    public static function obtenerTotalesNomina(int $idPagoNomina, string $fechaInicio, string $fechaFin): array
    {
        $detalle = self::obtenerDetalleNomina($idPagoNomina, $fechaInicio, $fechaFin);

        return [
            'total_registros' => $detalle->count(),
            'empleados_con_datos' => $detalle->pluck('id_empleado')->unique()->count(),
            'dias_trabajados' => round((float) $detalle->sum('dia_trabajo'), 2),
            'total_faltas' => $detalle->where('estado', 'F')->count(),
            'total_asistencias' => $detalle->where('estado', 'A')->count(),
        ];
    }

    public static function obtenerResumenEmpleado(
        int $idEmpleado,
        int $idPagoNomina,
        string $fechaInicio,
        string $fechaFin
    ): array {
        $registros = self::obtenerPorNomina($idPagoNomina, $fechaInicio, $fechaFin)
            ->where('id_empleado', $idEmpleado);

        if ($registros->isEmpty()) {
            return [
                'tiene_asistencias' => false,
                'dias_laborados' => 0,
                'faltas' => 0,
            ];
        }

        $diasLaborados = round((float) $registros->sum('dia_trabajo'), 2);
        $faltas = $registros->where('estado', 'F')->count();

        return [
            'tiene_asistencias' => true,
            'dias_laborados' => $diasLaborados,
            'faltas' => $faltas,
        ];
    }

    public static function procesarPeriodoEmpleado(
        int $idPagoNomina,
        int $idEmpleado,
        string $fechaInicio,
        string $fechaFin,
        array $checadasPorFecha,
        string $usuario
    ): int {
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);
        $fechaActual = $inicio->copy();
        $procesados = 0;

        while ($fechaActual->lte($fin)) {
            $fecha = $fechaActual->format('Y-m-d');
            $checada = $checadasPorFecha[$fecha] ?? ['entrada' => null, 'salida' => null];

            self::guardarAsistencia(
                $idPagoNomina,
                $idEmpleado,
                $fecha,
                $checada['entrada'] ?? null,
                $checada['salida'] ?? null,
                $usuario
            );

            $procesados++;
            $fechaActual->addDay();
        }

        return $procesados;
    }

    public static function procesarNominaDesdeImportacion(
        int $idPagoNomina,
        string $fechaInicio,
        string $fechaFin,
        array $checadasPorEmpleado,
        string $usuario
    ): int {
        AsistenciasCalculoService::limpiarCache();

        $empleadosNomina = DB::table('tblnominas_pagodet')
            ->where('idpagonomina', $idPagoNomina)
            ->pluck('idempleado')
            ->map(fn ($id) => (int) $id)
            ->all();

        $total = 0;

        foreach ($empleadosNomina as $idEmpleado) {
            if (!empty($checadasPorEmpleado) && !array_key_exists($idEmpleado, $checadasPorEmpleado)) {
                continue;
            }

            $checadasEmpleado = $checadasPorEmpleado[$idEmpleado] ?? [];

            $total += self::procesarPeriodoEmpleado(
                $idPagoNomina,
                $idEmpleado,
                $fechaInicio,
                $fechaFin,
                $checadasEmpleado,
                $usuario
            );
        }

        return $total;
    }

    public static function guardarAsistencia(
        int $idPagoNomina,
        int $idEmpleado,
        string $fecha,
        ?string $entrada,
        ?string $salida,
        string $usuario
    ): void {
        $where = [
            'id_empleado' => $idEmpleado,
            'fecha' => $fecha,
        ];

        if (self::usaIdPagoNomina()) {
            $where['idpagonomina'] = $idPagoNomina;
        }

        $existente = DB::table('tblnomina_asistencias')->where($where)->first();
        $idHorarioOverride = !empty($existente?->id_horario) ? (int) $existente->id_horario : null;

        $calculo = AsistenciasCalculoService::calcularDia(
            $idEmpleado,
            $fecha,
            $entrada,
            $salida,
            $idHorarioOverride
        );

        $ahora = now();

        $datos = [
            'entrada' => $entrada,
            'salida' => $salida,
            'estado' => $calculo['estado'],
            'dia_trabajo' => $calculo['dia_trabajo'],
            'comentario' => $calculo['comentario'],
            'updated_at' => $ahora,
            'updated_by' => $usuario,
        ];

        if ($existente) {
            DB::table('tblnomina_asistencias')
                ->where('id', $existente->id)
                ->update($datos);

            return;
        }

        $insert = array_merge($where, $datos, [
            'created_at' => $ahora,
            'created_by' => $usuario,
        ]);

        DB::table('tblnomina_asistencias')->insert($insert);
    }

    protected static function validarRegistroEdicion(
        int $idRegistro,
        int $idPagoNomina,
        string $fechaInicio,
        string $fechaFin
    ): object {
        $registro = DB::table('tblnomina_asistencias')->where('id', $idRegistro)->first();

        if (!$registro) {
            throw new \InvalidArgumentException('Registro de asistencia no encontrado.');
        }

        if ($registro->fecha < $fechaInicio || $registro->fecha > $fechaFin) {
            throw new \InvalidArgumentException('La fecha no corresponde al periodo de la nómina.');
        }

        $enNomina = DB::table('tblnominas_pagodet')
            ->where('idpagonomina', $idPagoNomina)
            ->where('idempleado', $registro->id_empleado)
            ->exists();

        if (!$enNomina) {
            throw new \InvalidArgumentException('El empleado no pertenece a esta nómina.');
        }

        if (self::usaIdPagoNomina() && (int) $registro->idpagonomina !== $idPagoNomina) {
            throw new \InvalidArgumentException('El registro no pertenece a esta nómina.');
        }

        return $registro;
    }

    public static function normalizarHora(?string $valor): ?string
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        $texto = trim($valor);

        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $texto)) {
            return strlen($texto) === 5 ? $texto . ':00' : $texto;
        }

        try {
            return Carbon::parse($texto)->format('H:i:s');
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("El formato de hora '{$texto}' no es válido. Use formato 24 horas, por ejemplo 08:00.");
        }
    }

    public static function actualizarChecadasManual(
        int $idRegistro,
        int $idPagoNomina,
        string $fechaInicio,
        string $fechaFin,
        ?string $entrada,
        ?string $salida,
        string $usuario
    ): array {
        $registro = self::validarRegistroEdicion($idRegistro, $idPagoNomina, $fechaInicio, $fechaFin);

        $entrada = self::normalizarHora($entrada);
        $salida = self::normalizarHora($salida);
        $idHorarioOverride = !empty($registro->id_horario) ? (int) $registro->id_horario : null;

        $calculo = AsistenciasCalculoService::calcularDia(
            (int) $registro->id_empleado,
            $registro->fecha,
            $entrada,
            $salida,
            $idHorarioOverride
        );

        DB::table('tblnomina_asistencias')
            ->where('id', $idRegistro)
            ->update([
                'entrada' => $entrada,
                'salida' => $salida,
                'estado' => $calculo['estado'],
                'dia_trabajo' => $calculo['dia_trabajo'],
                'comentario' => $calculo['comentario'],
                'updated_at' => now(),
                'updated_by' => $usuario,
            ]);

        return [
            'entrada' => $entrada ? substr($entrada, 0, 5) : null,
            'salida' => $salida ? substr($salida, 0, 5) : null,
            'estado' => $calculo['estado'],
            'dia_trabajo' => $calculo['dia_trabajo'],
            'comentario' => $calculo['comentario'],
        ];
    }

    public static function actualizarEstadoManual(
        int $idRegistro,
        int $idPagoNomina,
        string $fechaInicio,
        string $fechaFin,
        string $estado,
        string $usuario
    ): array {
        $registro = self::validarRegistroEdicion($idRegistro, $idPagoNomina, $fechaInicio, $fechaFin);

        $estado = strtoupper($estado);

        if (!in_array($estado, ['A', 'F'], true)) {
            throw new \InvalidArgumentException('Estado no válido.');
        }

        if ($estado === 'F') {
            $diaTrabajo = 0.0;
            $comentario = 'Estado corregido manualmente a Falta';
        } else {
            $idHorarioOverride = !empty($registro->id_horario) ? (int) $registro->id_horario : null;

            $calculo = AsistenciasCalculoService::calcularDia(
                (int) $registro->id_empleado,
                $registro->fecha,
                $registro->entrada,
                $registro->salida,
                $idHorarioOverride
            );

            $horarioDia = AsistenciasCalculoService::resolverHorario(
                $idHorarioOverride,
                (int) $registro->id_empleado,
                $registro->fecha
            );

            if ($horarioDia !== null && AsistenciasCalculoService::esDiaDescanso($horarioDia)) {
                $diaTrabajo = 1.0;
                $comentario = 'Estado corregido manualmente: día de descanso (1 día pagado)';
            } elseif ($calculo['dia_trabajo'] > 0) {
                $diaTrabajo = $calculo['dia_trabajo'];
                $comentario = 'Estado corregido manualmente a Asistencia';
            } else {
                $diaTrabajo = 1.0;
                $comentario = 'Estado corregido manualmente a Asistencia (día completo)';
            }
        }

        DB::table('tblnomina_asistencias')
            ->where('id', $idRegistro)
            ->update([
                'estado' => $estado,
                'dia_trabajo' => $diaTrabajo,
                'comentario' => $comentario,
                'updated_at' => now(),
                'updated_by' => $usuario,
            ]);

        return [
            'estado' => $estado,
            'dia_trabajo' => $diaTrabajo,
            'comentario' => $comentario,
        ];
    }

    public static function actualizarHorarioManual(
        int $idRegistro,
        int $idPagoNomina,
        string $fechaInicio,
        string $fechaFin,
        ?int $idHorario,
        string $usuario
    ): array {
        $registro = self::validarRegistroEdicion($idRegistro, $idPagoNomina, $fechaInicio, $fechaFin);

        if ($idHorario !== null && $idHorario > 0) {
            $existeHorario = DB::table('tblhorarios')->where('id', $idHorario)->exists();

            if (!$existeHorario) {
                throw new \InvalidArgumentException('El horario seleccionado no existe.');
            }
        }

        $horarioEmpleado = AsistenciasCalculoService::obtenerHorarioEmpleadoDia(
            (int) $registro->id_empleado,
            $registro->fecha
        );
        $horarioEmpleadoId = $horarioEmpleado?->id ?? null;

        $idHorarioGuardado = ($idHorario !== null && $idHorario > 0) ? $idHorario : null;

        if ($idHorarioGuardado !== null && (int) $horarioEmpleadoId === $idHorarioGuardado) {
            $idHorarioGuardado = null;
        }

        $calculo = AsistenciasCalculoService::calcularDia(
            (int) $registro->id_empleado,
            $registro->fecha,
            $registro->entrada,
            $registro->salida,
            $idHorarioGuardado
        );

        $horarioEfectivo = AsistenciasCalculoService::resolverHorario(
            $idHorarioGuardado,
            (int) $registro->id_empleado,
            $registro->fecha
        );

        DB::table('tblnomina_asistencias')
            ->where('id', $idRegistro)
            ->update([
                'id_horario' => $idHorarioGuardado,
                'estado' => $calculo['estado'],
                'dia_trabajo' => $calculo['dia_trabajo'],
                'comentario' => $calculo['comentario'],
                'updated_at' => now(),
                'updated_by' => $usuario,
            ]);

        $horarioIdSelect = $idHorarioGuardado ?? $horarioEmpleadoId;

        return [
            'horario_id' => $horarioIdSelect,
            'horario_texto' => AsistenciasCalculoService::formatearHorario($horarioEfectivo),
            'estado' => $calculo['estado'],
            'dia_trabajo' => $calculo['dia_trabajo'],
            'comentario' => $calculo['comentario'],
        ];
    }

    public static function recalcularTodos(
        int $idPagoNomina,
        string $fechaInicio,
        string $fechaFin,
        string $usuario
    ): int {
        AsistenciasCalculoService::limpiarCache();

        $empleadoIds = DB::table('tblnominas_pagodet')
            ->where('idpagonomina', $idPagoNomina)
            ->pluck('idempleado');

        $query = DB::table('tblnomina_asistencias')
            ->whereIn('id_empleado', $empleadoIds)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if (self::usaIdPagoNomina()) {
            $query->where('idpagonomina', $idPagoNomina);
        }

        $registros = $query->get();
        $actualizados = 0;
        $ahora = now();

        foreach ($registros as $registro) {
            $idHorarioOverride = !empty($registro->id_horario) ? (int) $registro->id_horario : null;

            $calculo = AsistenciasCalculoService::calcularDia(
                (int) $registro->id_empleado,
                $registro->fecha,
                $registro->entrada,
                $registro->salida,
                $idHorarioOverride
            );

            DB::table('tblnomina_asistencias')
                ->where('id', $registro->id)
                ->update([
                    'estado' => $calculo['estado'],
                    'dia_trabajo' => $calculo['dia_trabajo'],
                    'comentario' => $calculo['comentario'],
                    'updated_at' => $ahora,
                    'updated_by' => $usuario,
                ]);

            $actualizados++;
        }

        return $actualizados;
    }

    public static function eliminarPorNomina(int $idPagoNomina): int
    {
        if (!self::usaIdPagoNomina()) {
            return 0;
        }

        return DB::table('tblnomina_asistencias')
            ->where('idpagonomina', $idPagoNomina)
            ->delete();
    }
}
