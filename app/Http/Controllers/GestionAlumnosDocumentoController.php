<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GestionAlumnosDocumentoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Documento::query()->orderBy('nombre_documento');

        if ($request->boolean('solo_activas') && Schema::hasColumn('tbldocumentos', 'activo')) {
            $query->where(function ($q) {
                $q->where('activo', true)->orWhereNull('activo');
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function show(int $id): JsonResponse
    {
        $documento = Documento::findOrFail($id);

        return response()->json(['data' => $documento]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre_documento' => ['required', 'string', 'max:200'],
            'descripcion_documento' => ['nullable', 'string', 'max:300'],
            'otros_conceptos1' => ['nullable', 'string', 'max:20'],
            'otros_conceptos2' => ['nullable', 'string', 'max:250'],
            'otros_conceptos34' => ['nullable', 'numeric'],
        ]);

        $validated['activo'] = true;

        $documento = Documento::create($validated);

        return response()->json([
            'message' => 'Tipo de documento registrado correctamente.',
            'data' => $documento,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $documento = Documento::findOrFail($id);

        $validated = $request->validate([
            'nombre_documento' => ['required', 'string', 'max:200'],
            'descripcion_documento' => ['nullable', 'string', 'max:300'],
            'otros_conceptos1' => ['nullable', 'string', 'max:20'],
            'otros_conceptos2' => ['nullable', 'string', 'max:250'],
            'otros_conceptos34' => ['nullable', 'numeric'],
        ]);

        $documento->update($validated);

        return response()->json([
            'message' => 'Tipo de documento actualizado correctamente.',
            'data' => $documento->fresh(),
        ]);
    }

    public function inactivar(int $id): JsonResponse
    {
        $documento = Documento::findOrFail($id);

        if (! Schema::hasColumn('tbldocumentos', 'activo')) {
            return response()->json(['message' => 'La columna activo no existe en tbldocumentos. Ejecuta las migraciones.'], 503);
        }

        if ($documento->activo === false) {
            return response()->json(['message' => 'El tipo de documento ya estaba inactivo.'], 422);
        }

        $documento->update(['activo' => false]);

        return response()->json([
            'message' => 'Tipo de documento inactivado correctamente.',
            'data' => $documento->fresh(),
        ]);
    }
}
