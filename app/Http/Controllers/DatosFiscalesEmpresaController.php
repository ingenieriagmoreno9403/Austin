<?php

namespace App\Http\Controllers;

use App\Models\DatosFiscalesEmpresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DatosFiscalesEmpresaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DatosFiscalesEmpresa::query()->orderByDesc('id');

        $buscar = $request->query('buscar');
        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('razon_social', 'like', "%{$buscar}%")
                  ->orWhere('rfc', 'like', "%{$buscar}%");
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function show(int $id): JsonResponse
    {
        $row = DatosFiscalesEmpresa::findOrFail($id);
        return response()->json(['data' => $row]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'razon_social' => ['required', 'string', 'max:300'],
            'rfc' => ['required', 'string', 'max:13', 'unique:tbl_datos_fiscales_empresa,rfc'],
            'regimen_fiscal' => ['required', 'string', 'max:10'],
            'regimen_fiscal_descripcion' => ['nullable', 'string', 'max:200'],
            'calle' => ['nullable', 'string', 'max:200'],
            'numero_exterior' => ['nullable', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['nullable', 'string', 'max:150'],
            'municipio' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', 'string', 'max:100'],
            'codigo_postal' => ['required', 'string', 'max:10'],
            'pais' => ['nullable', 'string', 'max:50'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:200'],
            'estatus' => ['nullable', 'in:ACTIVO,INACTIVO'],
            'usr_facturama' => ['nullable', 'string', 'max:150'],
            'pwd_facturama' => ['nullable', 'string', 'max:255'],
        ]);

        $row = DatosFiscalesEmpresa::create($validated);

        return response()->json([
            'message' => 'Datos fiscales registrados correctamente.',
            'data' => $row,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $row = DatosFiscalesEmpresa::findOrFail($id);

        $validated = $request->validate([
            'razon_social' => ['required', 'string', 'max:300'],
            'rfc' => ['required', 'string', 'max:13', 'unique:tbl_datos_fiscales_empresa,rfc,' . $id],
            'regimen_fiscal' => ['required', 'string', 'max:10'],
            'regimen_fiscal_descripcion' => ['nullable', 'string', 'max:200'],
            'calle' => ['nullable', 'string', 'max:200'],
            'numero_exterior' => ['nullable', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['nullable', 'string', 'max:150'],
            'municipio' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', 'string', 'max:100'],
            'codigo_postal' => ['required', 'string', 'max:10'],
            'pais' => ['nullable', 'string', 'max:50'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:200'],
            'estatus' => ['nullable', 'in:ACTIVO,INACTIVO'],
            'usr_facturama' => ['nullable', 'string', 'max:150'],
            'pwd_facturama' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($validated['pwd_facturama'])) {
            unset($validated['pwd_facturama']);
        }

        $row->update($validated);

        return response()->json([
            'message' => 'Datos fiscales actualizados correctamente.',
            'data' => $row,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $row = DatosFiscalesEmpresa::findOrFail($id);
        $row->delete();

        return response()->json(['message' => 'Registro eliminado correctamente.']);
    }
}
