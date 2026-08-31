<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestionAlumnosDocenteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = Docente::query()->orderByDesc('id');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('primer_nombre', 'like', "%{$q}%")
                    ->orWhere('segundo_nombre', 'like', "%{$q}%")
                    ->orWhere('apellido_paterno', 'like', "%{$q}%")
                    ->orWhere('apellido_materno', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('especialidad', 'like', "%{$q}%")
                    ->orWhere('grado_academico', 'like', "%{$q}%");
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Docente::findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validar($request);
        $row = Docente::create($validated);

        return response()->json([
            'message' => 'Docente registrado correctamente.',
            'data' => $row,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $row = Docente::findOrFail($id);
        $validated = $this->validar($request, $row);
        $row->update($validated);

        return response()->json([
            'message' => 'Docente actualizado correctamente.',
            'data' => $row->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $row = Docente::findOrFail($id);
        $row->delete();

        return response()->json(['message' => 'Docente eliminado correctamente.']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validar(Request $request, ?Docente $existing = null): array
    {
        return $request->validate([
            'primer_nombre'   => ['required', 'string', 'max:100'],
            'segundo_nombre'  => ['nullable', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'email'           => ['nullable', 'string', 'email', 'max:200'],
            'telefono'        => ['nullable', 'string', 'max:20'],
            'especialidad'    => ['nullable', 'string', 'max:200'],
            'grado_academico' => ['nullable', 'string', 'max:150'],
            'estatus'         => ['nullable', 'string', 'in:ACTIVO,INACTIVO'],
        ]);
    }
}
