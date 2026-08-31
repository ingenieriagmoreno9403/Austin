<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\ConvenioAlumnoEmpresa;
use App\Models\GestionAlumnosEmpresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GestionAlumnosConvenioAsignacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $estado = $request->query('estado', 'activo');
        $q = ConvenioAlumnoEmpresa::query()
            ->with([
                'empresa:id,nombre',
                'alumno:id,nombres,apellido_paterno,apellido_materno,nunero_matricula',
            ])
            ->orderByDesc('id');

        if ($estado !== 'todos' && $estado !== 'all') {
            $q->where('estado', $estado);
        }

        $rows = $q->get()->map(function (ConvenioAlumnoEmpresa $c) {
            $a = $c->alumno;
            $nombreAlumno = $a
                ? trim(implode(' ', array_filter([
                    (string) ($a->nombres ?? ''),
                    (string) ($a->apellido_paterno ?? ''),
                    (string) ($a->apellido_materno ?? ''),
                ])))
                : '';

            return [
                'id' => (int) $c->id,
                'alumno_id' => (int) $c->alumno_id,
                'empresa_id' => (int) $c->empresa_id,
                'empresa_nombre' => $c->empresa ? (string) $c->empresa->nombre : '',
                'numero_matricula' => $a ? (int) ($a->nunero_matricula ?? 0) : null,
                'alumno_nombre' => $nombreAlumno !== '' ? $nombreAlumno : 'Alumno',
                'fecha_inicio' => $c->fecha_inicio ? $c->fecha_inicio->format('Y-m-d') : null,
                'fecha_fin' => $c->fecha_fin ? $c->fecha_fin->format('Y-m-d') : null,
                'estado' => (string) $c->estado,
                'observaciones' => $c->observaciones,
                'cumple' => true,
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alumno_id' => ['required', 'integer', 'exists:tblalumnos,id'],
            'empresa_id' => ['required', 'integer', 'exists:tblga_empresas,id'],
            'fecha_inicio' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $alumnoId = (int) $validated['alumno_id'];
        $empresaId = (int) $validated['empresa_id'];

        $otroActivo = ConvenioAlumnoEmpresa::query()
            ->activos()
            ->where('alumno_id', $alumnoId)
            ->first();

        if ($otroActivo !== null) {
            return response()->json([
                'message' => 'Este alumno ya tiene un convenio activo con otra empresa. Finaliza ese convenio antes de asignarlo de nuevo.',
                'error' => 'alumno_ya_asignado',
                'empresa_actual_id' => (int) $otroActivo->empresa_id,
            ], 422);
        }

        $fechaInicio = isset($validated['fecha_inicio'])
            ? $validated['fecha_inicio']
            : now()->toDateString();

        $alumno = Alumno::findOrFail($alumnoId);
        $empresa = GestionAlumnosEmpresa::findOrFail($empresaId);

        $nombreAlumno = trim(implode(' ', array_filter([
            (string) ($alumno->nombres ?? ''),
            (string) ($alumno->apellido_paterno ?? ''),
            (string) ($alumno->apellido_materno ?? ''),
        ])));

        $creado = DB::transaction(function () use ($alumnoId, $empresaId, $fechaInicio, $validated) {
            return ConvenioAlumnoEmpresa::create([
                'alumno_id' => $alumnoId,
                'empresa_id' => $empresaId,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => null,
                'estado' => 'activo',
                'observaciones' => $validated['observaciones'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Asignación registrada correctamente.',
            'data' => [
                'id' => (int) $creado->id,
                'alumno_id' => $alumnoId,
                'empresa_id' => $empresaId,
                'empresa_nombre' => (string) ($empresa->nombre ?? ''),
                'numero_matricula' => (int) ($alumno->numero_matricula ?? 0),
                'alumno_nombre' => $nombreAlumno !== '' ? $nombreAlumno : 'Alumno',
                'fecha_inicio' => $creado->fecha_inicio ? $creado->fecha_inicio->format('Y-m-d') : null,
                'fecha_fin' => null,
                'estado' => 'activo',
                'observaciones' => $creado->observaciones,
                'cumple' => true,
            ],
        ], 201);
    }

    public function terminar(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'fecha_fin' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $row = ConvenioAlumnoEmpresa::findOrFail($id);

        if ($row->estado !== 'activo') {
            return response()->json(['message' => 'El convenio ya no está activo.'], 422);
        }

        $fechaFin = $validated['fecha_fin'] ?? now()->toDateString();

        $row->fecha_fin = $fechaFin;
        $row->estado = 'terminado';
        if (array_key_exists('observaciones', $validated) && $validated['observaciones'] !== null) {
            $row->observaciones = $validated['observaciones'];
        }
        $row->save();

        return response()->json([
            'message' => 'Convenio marcado como terminado.',
            'data' => [
                'id' => (int) $row->id,
                'estado' => $row->estado,
                'fecha_fin' => $row->fecha_fin ? $row->fecha_fin->format('Y-m-d') : null,
            ],
        ]);
    }
}
