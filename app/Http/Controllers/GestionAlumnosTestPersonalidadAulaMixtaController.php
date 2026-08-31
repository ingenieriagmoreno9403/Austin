<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\TestPersonalidadAulaMixta;
use App\Models\TestPersonalidadAulaMixtaRespuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GestionAlumnosTestPersonalidadAulaMixtaController extends Controller
{
    public function showByAlumno(int $alumnoId): JsonResponse
    {
        $test = TestPersonalidadAulaMixta::where('alumno_id', $alumnoId)
            ->orderByDesc('id')
            ->first();

        if (!$test) {
            return response()->json(['data' => null]);
        }

        $respuestas = TestPersonalidadAulaMixtaRespuesta::where('test_id', $test->id)
            ->orderBy('reactivo_numero')
            ->get()
            ->map(function ($r) {
                return [
                    'reactivo_numero' => (int) $r->reactivo_numero,
                    'respuesta_valor' => (int) $r->respuesta_valor,
                ];
            });

        return response()->json([
            'data' => [
                'id' => $test->id,
                'alumno_id' => $test->alumno_id,
                'grupo' => $test->grupo,
                'fecha_aplicacion' => $test->fecha_aplicacion,
                'puntaje_total' => $test->puntaje_total,
                'interpretacion' => $test->interpretacion,
                'rango_bajo' => $test->rango_bajo,
                'rango_medio' => $test->rango_medio,
                'rango_alto' => $test->rango_alto,
                'respuestas' => $respuestas,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alumno_id' => ['required', 'integer', 'exists:tblalumnos,id'],
            'grupo' => ['nullable', 'string', 'max:100'],
            'fecha_aplicacion' => ['nullable', 'date'],
            'puntaje_total' => ['required', 'integer', 'min:0'],
            'interpretacion' => ['nullable', 'string', 'max:150'],
            'resultados_columnas' => ['nullable', 'array'],
            'rango_bajo' => ['nullable', 'string', 'max:30'],
            'rango_medio' => ['nullable', 'string', 'max:30'],
            'rango_alto' => ['nullable', 'string', 'max:30'],
            'respuestas' => ['required', 'array', 'size:80'],
            'respuestas.*' => ['integer', 'in:0,1'],
        ]);

        $alumno = Alumno::findOrFail((int) $validated['alumno_id']);
        $alumnoNombre = trim(implode(' ', [
            (string) ($alumno->nombres ?? ''),
            (string) ($alumno->apellido_paterno ?? ''),
            (string) ($alumno->apellido_materno ?? ''),
        ]));

        $test = DB::transaction(function () use ($validated, $alumno, $alumnoNombre) {
            $test = TestPersonalidadAulaMixta::create([
                'alumno_id' => (int) $alumno->id,
                'numero_matricula' => $alumno->numero_matricula,
                'alumno_nombre' => $alumnoNombre,
                'grupo' => $validated['grupo'] ?? null,
                'fecha_aplicacion' => $validated['fecha_aplicacion'] ?? null,
                'puntaje_total' => (int) $validated['puntaje_total'],
                'interpretacion' => $validated['interpretacion'] ?? null,
                'resultados_columnas_json' => isset($validated['resultados_columnas'])
                    ? json_encode($validated['resultados_columnas'], JSON_UNESCAPED_UNICODE)
                    : null,
                'rasgos_positivos_total' => isset($validated['resultados_columnas']) && is_array($validated['resultados_columnas'])
                    ? (int) collect($validated['resultados_columnas'])->filter(function ($r) {
                        return isset($r['es_positivo']) && (bool) $r['es_positivo'] === true;
                    })->count()
                    : 0,
                'rango_bajo' => $validated['rango_bajo'] ?? null,
                'rango_medio' => $validated['rango_medio'] ?? null,
                'rango_alto' => $validated['rango_alto'] ?? null,
            ]);

            $rows = [];
            foreach (array_values($validated['respuestas']) as $idx => $valor) {
                $rows[] = [
                    'test_id' => (int) $test->id,
                    'alumno_id' => (int) $alumno->id,
                    'reactivo_numero' => $idx + 1,
                    'respuesta_valor' => (int) $valor,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            TestPersonalidadAulaMixtaRespuesta::insert($rows);

            return $test;
        });

        return response()->json([
            'message' => 'Test guardado correctamente.',
            'data' => [
                'id' => $test->id,
                'alumno_id' => $test->alumno_id,
                'puntaje_total' => $test->puntaje_total,
                'interpretacion' => $test->interpretacion,
            ],
        ], 201);
    }
}
