<?php

namespace App\Http\Controllers;

use App\Models\GestionAlumnosEmpresa;
use App\Models\GestionAlumnosVisitaProspeccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestionAlumnosEmpresaVisitaController extends Controller
{
    public function index(int $empresa): JsonResponse
    {
        GestionAlumnosEmpresa::findOrFail($empresa);

        $data = GestionAlumnosVisitaProspeccion::query()
            ->where('empresa_id', $empresa)
            ->orderByDesc('fecha_visita')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request, int $empresa): JsonResponse
    {
        GestionAlumnosEmpresa::findOrFail($empresa);

        $validated = $request->validate([
            'fecha_visita' => ['required', 'date'],
            'contacto_empresa' => ['nullable', 'string', 'max:150'],
            'puesto_contacto' => ['nullable', 'string', 'max:100'],
            'presentacion_sed' => ['nullable', 'boolean'],
            'acepta_adoptar_sed' => ['nullable', 'in:Pendiente,Aceptado,Rechazado'],
            'vacantes_disponibles' => ['nullable', 'integer', 'min:0'],
            'perfil_buscado' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $validated['empresa_id'] = $empresa;
        $validated['presentacion_sed'] = (bool) ($validated['presentacion_sed'] ?? false);
        $validated['acepta_adoptar_sed'] = $validated['acepta_adoptar_sed'] ?? 'Pendiente';
        $validated['vacantes_disponibles'] = $validated['vacantes_disponibles'] ?? 0;

        $row = GestionAlumnosVisitaProspeccion::create($validated);

        return response()->json([
            'message' => 'Visita registrada correctamente.',
            'data' => $row,
        ], 201);
    }
}
