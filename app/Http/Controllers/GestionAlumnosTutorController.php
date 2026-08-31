<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Tutor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestionAlumnosTutorController extends Controller
{
    /**
     * Tutores registrados en tbltutores para el alumno indicado (tblalumnos.id).
     */
    public function indexPorAlumno(int $id): JsonResponse
    {
        Alumno::query()->findOrFail($id);

        $data = Tutor::query()
            ->where('alumno_id', $id)
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        Alumno::query()->findOrFail($id);

        $validated = $request->validate([
            'nombres' => ['required', 'string'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'direccion' => ['nullable', 'string'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['required', 'email', 'max:150'],
            'parentesco' => ['nullable', 'string', 'max:225'],
            'otros_conceptos1' => ['nullable', 'string', 'max:20'],
            'otros_conceptos2' => ['nullable', 'string', 'max:250'],
            'otros_conceptos34' => ['nullable', 'numeric'],
        ]);

        $parentesco = $validated['parentesco'] ?? null;
        $validated['paretensco'] = (is_string($parentesco) && trim($parentesco) !== '') ? trim($parentesco) : null;
        unset($validated['parentesco']);
        $validated['alumno_id'] = $id;

        foreach (['apellido_materno', 'direccion', 'telefono'] as $campo) {
            if (array_key_exists($campo, $validated) && is_string($validated[$campo]) && trim($validated[$campo]) === '') {
                $validated[$campo] = null;
            }
        }

        $row = Tutor::create($validated);

        return response()->json([
            'message' => 'Tutor registrado correctamente.',
            'data' => $row,
        ], 201);
    }
}
