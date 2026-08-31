<?php

namespace App\Http\Controllers;

use App\Models\ProductoConexionEspecificacion;
use App\Models\Productos;
use App\Services\ImportarConexionEspecificacionesService;
use App\Traits\MenuTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConexionEspecificacionesController extends Controller
{
    use MenuTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $productoId = (int) $request->input('producto_id', 0);
        $buscar = trim((string) $request->input('buscar', ''));
        $tipo = trim((string) $request->input('tipo', ''));

        $query = ProductoConexionEspecificacion::with('producto')
            ->orderBy('tipo')
            ->orderBy('diametro_mm')
            ->orderBy('rd');

        if ($request->input('estatus') === 'INACTIVO') {
            $query->where('estatus', 'INACTIVO');
        } elseif ($request->input('estatus') !== 'TODOS') {
            $query->where('estatus', 'ACTIVO');
        }

        if ($productoId > 0) {
            $query->where('producto_id', $productoId);
        }
        if ($tipo !== '') {
            $query->where('tipo', $tipo);
        }
        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('tipo', 'like', "%{$buscar}%")
                    ->orWhere('diametro_nominal', 'like', "%{$buscar}%")
                    ->orWhere('material', 'like', "%{$buscar}%")
                    ->orWhereHas('producto', function ($pq) use ($buscar) {
                        $pq->where('sku', 'like', "%{$buscar}%")
                            ->orWhere('nombre', 'like', "%{$buscar}%");
                    });
            });
        }

        $especificaciones = $query->get();
        $totalEspecificaciones = ProductoConexionEspecificacion::count();
        $tipos = ProductoConexionEspecificacion::query()
            ->select('tipo')
            ->distinct()
            ->orderBy('tipo')
            ->pluck('tipo');

        $productos = Productos::query()
            ->where(function ($q) {
                $q->where('tipo_proceso', Productos::TIPO_PROCESO_CONEXION)
                    ->orWhereIn('id', ProductoConexionEspecificacion::query()->select('producto_id')->distinct());
            })
            ->orderBy('nombre')
            ->limit(800)
            ->get(['id', 'sku', 'nombre']);

        return view('Produccion.especificaciones_conexiones', compact(
            'varpantallas',
            'varsubmenus',
            'especificaciones',
            'productos',
            'productoId',
            'totalEspecificaciones',
            'buscar',
            'tipo',
            'tipos'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:tblproductos,id'],
            'codigo' => ['nullable', 'string', 'max:80'],
            'tipo' => ['required', 'string', 'max:80'],
            'diametro_mm' => ['nullable', 'numeric', 'min:0'],
            'diametro_nominal' => ['nullable', 'string', 'max:30'],
            'rd' => ['nullable', 'numeric', 'min:0'],
            'peso_tubo_kg_m' => ['nullable', 'numeric', 'min:0'],
            'peso_kg_pieza' => ['nullable', 'numeric', 'min:0'],
            'material' => ['nullable', 'string', 'max:80'],
            'estatus' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
        ]);

        DB::transaction(function () use ($validated) {
            ProductoConexionEspecificacion::updateOrCreate(
                ['producto_id' => $validated['producto_id']],
                $validated
            );
            DB::table('tblproductos')->where('id', $validated['producto_id'])->update([
                'tipo_proceso' => Productos::TIPO_PROCESO_CONEXION,
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('produccion.especificaciones.conexiones')
            ->with('success', 'Especificación de conexión guardada.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $spec = ProductoConexionEspecificacion::findOrFail($id);
        $validated = $request->validate([
            'codigo' => ['nullable', 'string', 'max:80'],
            'tipo' => ['required', 'string', 'max:80'],
            'diametro_mm' => ['nullable', 'numeric', 'min:0'],
            'diametro_nominal' => ['nullable', 'string', 'max:30'],
            'rd' => ['nullable', 'numeric', 'min:0'],
            'peso_tubo_kg_m' => ['nullable', 'numeric', 'min:0'],
            'peso_kg_pieza' => ['nullable', 'numeric', 'min:0'],
            'material' => ['nullable', 'string', 'max:80'],
            'estatus' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
        ]);

        $spec->update($validated);

        return redirect()
            ->route('produccion.especificaciones.conexiones', request()->query())
            ->with('success', 'Especificación actualizada.');
    }

    public function importar(Request $request, ImportarConexionEspecificacionesService $service): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ]);

        $path = $request->file('archivo')->getRealPath();
        $resultado = $service->importar($path);

        return redirect()
            ->route('produccion.especificaciones.conexiones')
            ->with(
                'success',
                'Importación lista: ' . $resultado['importados'] . ' specs, '
                . $resultado['productos_nuevos'] . ' productos nuevos, '
                . $resultado['productos_actualizados'] . ' actualizados.'
            );
    }
}
