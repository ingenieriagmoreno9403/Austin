<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestionAlumnosCursoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = Curso::query()->orderByDesc('id');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nombre', 'like', "%{$q}%")
                    ->orWhere('descripcion', 'like', "%{$q}%")
                    ->orWhere('tipo_curso', 'like', "%{$q}%")
                    ->orWhere('duracion', 'like', "%{$q}%")
                    ->orWhere('instructor', 'like', "%{$q}%")
                    ->orWhere('aula_lugar', 'like', "%{$q}%")
                    ->orWhere('link', 'like', "%{$q}%");
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Curso::findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validar($request);
        $row = Curso::create($validated);

        return response()->json([
            'message' => 'Curso registrado correctamente.',
            'data' => $row,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $row = Curso::findOrFail($id);
        $validated = $this->validar($request);
        $row->update($validated);

        return response()->json([
            'message' => 'Curso actualizado correctamente.',
            'data' => $row->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $row = Curso::findOrFail($id);
        $row->delete();

        return response()->json(['message' => 'Curso eliminado correctamente.']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'tipo_curso' => ['nullable', 'string', 'max:100'],
            'duracion' => ['nullable', 'string', 'max:50'],
            'instructor' => ['nullable', 'string', 'max:150'],
            'link' => ['nullable', 'string', 'max:300'],
            'aula_lugar' => ['nullable', 'string', 'max:200'],
        ]);
    }
}
