<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestionAlumnosAlumnoCursoController extends Controller
{
    /**
     * Cursos asignados al alumno (incluye pivot: calificacion).
     */
    public function index(int $alumno): JsonResponse
    {
        $row = Alumno::findOrFail($alumno);
        $cursos = $row->cursos()
            ->orderBy('cursos.nombre')
            ->get();

        return response()->json(['data' => $cursos]);
    }

    public function store(Request $request, int $alumno): JsonResponse
    {
        $validated = $request->validate([
            'curso_id' => ['required', 'integer', 'exists:cursos,id'],
        ]);

        $row = Alumno::findOrFail($alumno);

        if ($row->cursos()->where('cursos.id', $validated['curso_id'])->exists()) {
            return response()->json([
                'message' => 'El alumno ya tiene asignado este curso.',
            ], 422);
        }

        try {
            $row->cursos()->attach($validated['curso_id'], [
                'calificacion' => null,
            ]);
        } catch (QueryException $e) {
            $sqlState = $e->errorInfo[1] ?? null;
            if ($sqlState === 1062 || str_contains((string) $e->getMessage(), 'Duplicate')) {
                return response()->json([
                    'message' => 'El alumno ya tiene asignado este curso.',
                ], 422);
            }
            throw $e;
        }

        $asignado = $row->cursos()->where('cursos.id', $validated['curso_id'])->first();

        return response()->json([
            'message' => 'Curso asignado correctamente.',
            'data' => $asignado,
        ], 201);
    }

    public function updateCalificacion(Request $request, int $alumno, int $curso): JsonResponse
    {
        $validated = $request->validate([
            'calificacion' => ['nullable', 'string', 'max:50'],
        ]);

        $row = Alumno::findOrFail($alumno);

        if (! $row->cursos()->where('cursos.id', $curso)->exists()) {
            return response()->json([
                'message' => 'El curso no está asignado al alumno.',
            ], 404);
        }

        $row->cursos()->updateExistingPivot($curso, [
            'calificacion' => isset($validated['calificacion']) && trim((string) $validated['calificacion']) !== ''
                ? trim((string) $validated['calificacion'])
                : null,
        ]);

        $actualizado = $row->cursos()->where('cursos.id', $curso)->first();

        return response()->json([
            'message' => 'Calificación actualizada correctamente.',
            'data' => $actualizado,
        ]);
    }

    public function destroy(int $alumno, int $curso): JsonResponse
    {
        $row = Alumno::findOrFail($alumno);
        $n = $row->cursos()->detach($curso);

        if ($n === 0) {
            return response()->json([
                'message' => 'No había una asignación de ese curso para este alumno.',
            ], 404);
        }

        return response()->json(['message' => 'Curso desasignado correctamente.']);
    }
}
