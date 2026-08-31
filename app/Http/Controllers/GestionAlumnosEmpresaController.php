<?php

namespace App\Http\Controllers;

use App\Models\GestionAlumnosEmpresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GestionAlumnosEmpresaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = GestionAlumnosEmpresa::query()->orderByDesc('id');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nombre', 'like', "%{$q}%")
                    ->orWhere('estado', 'like', "%{$q}%")
                    ->orWhere('municipio', 'like', "%{$q}%")
                    ->orWhere('giro_empresarial', 'like', "%{$q}%")
                    ->orWhere('correos', 'like', "%{$q}%");
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    /**
     * Empresas que tienen al menos una visita de prospección con FED “Aceptado” (tblvisitas_prospeccion.acepta_adoptar_sed).
     */
    public function empresasFedAceptadas(): JsonResponse
    {
        if (! Schema::hasTable('tblvisitas_prospeccion') || ! Schema::hasTable('tblga_empresas')) {
            return response()->json(['data' => []]);
        }

        // JOIN simple: compatible con MySQL/MariaDB (evita CAST en ENUM que en algunos servidores rompe la consulta).
        $data = GestionAlumnosEmpresa::query()
            ->join('tblvisitas_prospeccion', function ($join) {
                $join->on('tblvisitas_prospeccion.empresa_id', '=', 'tblga_empresas.id')
                    ->where('tblvisitas_prospeccion.acepta_adoptar_sed', 'Aceptado');
            })
            ->select('tblga_empresas.*')
            ->distinct()
            ->orderBy('tblga_empresas.nombre')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => GestionAlumnosEmpresa::findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validar($request);
        $validated['estado'] = 'A';
        $row = GestionAlumnosEmpresa::create($validated);

        return response()->json([
            'message' => 'Empresa registrada correctamente.',
            'data' => $row,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $row = GestionAlumnosEmpresa::findOrFail($id);
        $validated = $this->validar($request);
        $row->update($validated);

        return response()->json([
            'message' => 'Empresa actualizada correctamente.',
            'data' => $row->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $row = GestionAlumnosEmpresa::findOrFail($id);
        $row->delete();

        return response()->json(['message' => 'Empresa eliminada correctamente.']);
    }

    public function importarMasivo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $spreadsheet = IOFactory::load($validated['archivo']->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        if (!$rows || count($rows) < 2) {
            return response()->json(['message' => 'El archivo no contiene datos para importar.'], 422);
        }

        $creadas = 0;
        $existentes = 0;
        $vacias = 0;

        // Primera fila se considera encabezado.
        for ($i = 1; $i < count($rows); $i++) {
            $nombre = trim((string) ($rows[$i][0] ?? ''));
            if ($nombre === '' || mb_strtolower($nombre, 'UTF-8') === 'empresas') {
                $vacias++;
                continue;
            }

            $existe = GestionAlumnosEmpresa::query()
                ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower($nombre, 'UTF-8')])
                ->first();

            if ($existe) {
                $existentes++;
                continue;
            }

            GestionAlumnosEmpresa::query()->create([
                'nombre' => $nombre,
                'estado' => 'A',
            ]);
            $creadas++;
        }

        return response()->json([
            'message' => 'Importación de empresas finalizada.',
            'data' => [
                'creadas' => $creadas,
                'existentes' => $existentes,
                'filas_vacias' => $vacias,
            ],
        ]);
    }

    protected function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'direccion' => ['nullable', 'string'],
            'telefonos' => ['nullable', 'string', 'max:100'],
            'correos' => ['nullable', 'string', 'max:200'],
            'fecha_fin' => ['nullable', 'date'],
            'giro_empresarial' => ['nullable', 'string', 'max:200'],
            'municipio' => ['nullable', 'string', 'max:100'],
            'razon_social' => ['nullable', 'string', 'max:300'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'regimen_fiscal' => ['nullable', 'string', 'max:10'],
            'regimen_fiscal_descripcion' => ['nullable', 'string', 'max:200'],
            'calle' => ['nullable', 'string', 'max:200'],
            'numero_exterior' => ['nullable', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['nullable', 'string', 'max:150'],
            'codigo_postal' => ['nullable', 'string', 'max:10'],
            'uso_cfdi' => ['nullable', 'string', 'max:10'],
            'correo_fiscal' => ['nullable', 'string', 'max:200'],
        ]);
    }
}
