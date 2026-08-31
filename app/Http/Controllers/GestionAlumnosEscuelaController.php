<?php

namespace App\Http\Controllers;

use App\Models\Escuela;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GestionAlumnosEscuelaController extends Controller
{
    /**
     * Listado de escuelas (para modal: todas; para select: solo_activas=1).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Escuela::query()->orderBy('nombre');

        if ($request->boolean('solo_activas') && Schema::hasColumn('tblescuelas', 'activo')) {
            $query->where(function ($q) {
                $q->where('activo', true)->orWhereNull('activo');
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function show(int $id): JsonResponse
    {
        $escuela = Escuela::findOrFail($id);

        return response()->json(['data' => $escuela]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string'],
            'numero_sep' => ['required', 'string', 'max:50'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'otros_conceptos1' => ['nullable', 'string'],
            'otros_conceptos2' => ['nullable', 'string'],
            'otros_conceptos34' => ['nullable', 'string'],
        ]);

        $data = $this->mapToColumns($validated);
        $data['activo'] = true;

        $escuela = Escuela::create($data);

        return response()->json([
            'message' => 'Escuela registrada correctamente.',
            'data' => $escuela,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $escuela = Escuela::findOrFail($id);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string'],
            'numero_sep' => ['required', 'string', 'max:50'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'otros_conceptos1' => ['nullable', 'string'],
            'otros_conceptos2' => ['nullable', 'string'],
            'otros_conceptos34' => ['nullable', 'string'],
        ]);

        $escuela->update($this->mapToColumns($validated));

        return response()->json([
            'message' => 'Escuela actualizada correctamente.',
            'data' => $escuela->fresh(),
        ]);
    }

    public function inactivar(int $id): JsonResponse
    {
        $escuela = Escuela::findOrFail($id);

        if (! \Illuminate\Support\Facades\Schema::hasColumn('tblescuelas', 'activo')) {
            return response()->json(['message' => 'La columna activo no existe en tblescuelas. Ejecuta las migraciones.'], 503);
        }

        if ($escuela->activo === false) {
            return response()->json(['message' => 'La escuela ya estaba inactiva.'], 422);
        }

        $escuela->update(['activo' => false]);

        return response()->json([
            'message' => 'Escuela inactivada correctamente.',
            'data' => $escuela->fresh(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mapToColumns(array $validated): array
    {
        return [
            'nombre' => $validated['nombre'],
            'Direccion' => $validated['direccion'] ?? null,
            'numero_sep' => $validated['numero_sep'],
            'telefono' => $validated['telefono'] ?? null,
            'otros_conceptos1' => $validated['otros_conceptos1'] ?? null,
            'otros_conceptos2' => $validated['otros_conceptos2'] ?? null,
            'otros_conceptos34' => $validated['otros_conceptos34'] ?? null,
        ];
    }
}
