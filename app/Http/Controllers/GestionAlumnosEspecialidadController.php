<?php

namespace App\Http\Controllers;

use App\Models\Especialidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GestionAlumnosEspecialidadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Especialidad::query()->orderBy('nombre_especialidad');

        if ($request->boolean('solo_activas') && Schema::hasColumn('tblespecialidades', 'activo')) {
            $query->where(function ($q) {
                $q->where('activo', true)->orWhereNull('activo');
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function show(int $id): JsonResponse
    {
        $especialidad = Especialidad::findOrFail($id);

        return response()->json(['data' => $especialidad]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_interno_especialidad' => ['required', 'integer', 'min:1'],
            'nombre_especialidad' => ['required', 'string', 'max:100'],
            'descripcion' => ['required', 'string'],
            'otros_conceptos1' => ['nullable', 'string', 'max:20'],
            'otros_conceptos2' => ['nullable', 'string', 'max:250'],
            'otros_conceptos34' => ['nullable', 'numeric'],
        ]);

        $validated['activo'] = true;

        $especialidad = Especialidad::create($validated);

        return response()->json([
            'message' => 'Especialidad registrada correctamente.',
            'data' => $especialidad,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $especialidad = Especialidad::findOrFail($id);

        $validated = $request->validate([
            'numero_interno_especialidad' => ['required', 'integer', 'min:1'],
            'nombre_especialidad' => ['required', 'string', 'max:100'],
            'descripcion' => ['required', 'string'],
            'otros_conceptos1' => ['nullable', 'string', 'max:20'],
            'otros_conceptos2' => ['nullable', 'string', 'max:250'],
            'otros_conceptos34' => ['nullable', 'numeric'],
        ]);

        $especialidad->update($validated);

        return response()->json([
            'message' => 'Especialidad actualizada correctamente.',
            'data' => $especialidad->fresh(),
        ]);
    }

    public function inactivar(int $id): JsonResponse
    {
        $especialidad = Especialidad::findOrFail($id);

        if (! Schema::hasColumn('tblespecialidades', 'activo')) {
            return response()->json(['message' => 'La columna activo no existe en tblespecialidades. Ejecuta las migraciones.'], 503);
        }

        if ($especialidad->activo === false) {
            return response()->json(['message' => 'La especialidad ya estaba inactiva.'], 422);
        }

        $especialidad->update(['activo' => false]);

        return response()->json([
            'message' => 'Especialidad inactivada correctamente.',
            'data' => $especialidad->fresh(),
        ]);
    }
}
