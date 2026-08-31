<?php

namespace App\Http\Controllers;

use App\Models\ProductoTuboEspecificacion;
use App\Models\Productos;
use App\Services\ImportarTuboEspecificacionesService;
use App\Traits\MenuTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TuboEspecificacionesController extends Controller
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

        $productosConSpecs = ProductoTuboEspecificacion::query()
            ->select('producto_id', 'material')
            ->distinct()
            ->with('producto:id,nombre,sku')
            ->get()
            ->sortBy(fn ($item) => $item->producto->nombre ?? '')
            ->values();

        $productoId = (int) $request->input('producto_id', 0);
        $buscar = trim((string) $request->input('buscar', ''));

        $query = ProductoTuboEspecificacion::with('producto')
            ->orderBy('diametro_exterior_pulg')
            ->orderByDesc('psi');

        if ($request->input('estatus') === 'INACTIVO') {
            $query->where('estatus', 'INACTIVO');
        } elseif ($request->input('estatus') !== 'TODOS') {
            $query->where('estatus', 'ACTIVO');
        }

        if ($productoId > 0) {
            $query->where('producto_id', $productoId);
        }

        if ($request->filled('diametro_nominal')) {
            $query->where('diametro_nominal', $request->input('diametro_nominal'));
        }

        if ($request->filled('psi')) {
            $query->where('psi', $request->input('psi'));
        }

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('diametro_nominal', 'like', "%{$buscar}%")
                    ->orWhere('material', 'like', "%{$buscar}%")
                    ->orWhereHas('producto', function ($pq) use ($buscar) {
                        $pq->where('sku', 'like', "%{$buscar}%")
                            ->orWhere('nombre', 'like', "%{$buscar}%");
                    });
            });
        }

        $especificaciones = $query->get();
        $totalEspecificaciones = ProductoTuboEspecificacion::count();

        $diametros = ProductoTuboEspecificacion::query()
            ->when($productoId > 0, fn ($q) => $q->where('producto_id', $productoId))
            ->select('diametro_nominal')
            ->distinct()
            ->orderBy('diametro_exterior_pulg')
            ->pluck('diametro_nominal');

        $gradosPsi = ProductoTuboEspecificacion::query()
            ->when($productoId > 0, fn ($q) => $q->where('producto_id', $productoId))
            ->select('psi')
            ->distinct()
            ->orderByDesc('psi')
            ->pluck('psi');

        $productoSeleccionado = $productoId > 0 ? Productos::find($productoId) : null;
        $material = $especificaciones->first()?->material
            ?? ProductoTuboEspecificacion::where('producto_id', $productoId)->value('material')
            ?? 'PE-100';

        $productosTubo = $this->productosTuboCatalogo();

        return view('Produccion.especificaciones_tubo', compact(
            'varpantallas',
            'varsubmenus',
            'especificaciones',
            'productosConSpecs',
            'productosTubo',
            'productoId',
            'productoSeleccionado',
            'material',
            'diametros',
            'gradosPsi',
            'totalEspecificaciones',
            'buscar'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validarEspecificacion($request);

        DB::transaction(function () use ($validated) {
            $spec = ProductoTuboEspecificacion::create($validated);
            $this->sincronizarEspecificacionProducto((int) $spec->producto_id, (int) $spec->id);
        });

        return redirect()
            ->route('produccion.especificaciones.tubo', $this->filtrosRedirect($request))
            ->with('success', 'Especificación registrada correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $spec = ProductoTuboEspecificacion::findOrFail($id);
        $validated = $this->validarEspecificacion($request, $spec->id);

        DB::transaction(function () use ($spec, $validated) {
            $spec->update($validated);

            if ($spec->estatus === 'ACTIVO') {
                $this->sincronizarEspecificacionProducto((int) $spec->producto_id, (int) $spec->id);
            }
        });

        return redirect()
            ->route('produccion.especificaciones.tubo', $this->filtrosRedirect($request))
            ->with('success', 'Especificación actualizada correctamente.');
    }

    public function importar(Request $request, ImportarTuboEspecificacionesService $service): RedirectResponse
    {
        $validated = $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'producto_id' => ['nullable', 'integer'],
            'material' => ['nullable', 'string', 'max:100'],
        ]);

        $ruta = $request->file('archivo')->storeAs(
            'imports',
            'tubo_especificaciones_' . now()->format('Ymd_His') . '.xlsx'
        );

        $resultado = $service->importar(
            storage_path('app/' . $ruta),
            $validated['producto_id'] ?? null,
            $validated['material'] ?? 'HDPE PE4710'
        );

        return redirect()
            ->route('produccion.especificaciones.tubo')
            ->with('success', "Se crearon/vincularon {$resultado['productos']} productos por diámetro y {$resultado['importados']} especificaciones.");
    }

    public function vincularProductos(ImportarTuboEspecificacionesService $service): RedirectResponse
    {
        $resultado = $service->vincularProductosPorDiametro('HDPE PE4710');

        return redirect()
            ->route('produccion.especificaciones.tubo')
            ->with('success', "Se crearon/vincularon {$resultado['productos']} productos por diámetro y {$resultado['importados']} especificaciones.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validarEspecificacion(Request $request, ?int $ignorarId = null): array
    {
        $validated = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:tblproductos,id'],
            'material' => ['nullable', 'string', 'max:100'],
            'diametro_nominal' => ['required', 'string', 'max:50'],
            'diametro_exterior_pulg' => ['nullable', 'numeric', 'min:0'],
            'psi' => ['required', 'numeric', 'min:0'],
            'rd' => ['nullable', 'numeric', 'min:0'],
            'espesor_pulg' => ['nullable', 'numeric', 'min:0'],
            'peso_kg_m' => ['nullable', 'numeric', 'min:0'],
            'estatus' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
        ]);

        $duplicada = ProductoTuboEspecificacion::query()
            ->where('producto_id', $validated['producto_id'])
            ->where('diametro_nominal', $validated['diametro_nominal'])
            ->where('psi', $validated['psi'])
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->exists();

        if ($duplicada) {
            throw ValidationException::withMessages([
                'psi' => 'Ya existe una especificación con el mismo producto, diámetro nominal y PSI.',
            ]);
        }

        if (empty($validated['espesor_pulg']) && ! empty($validated['diametro_exterior_pulg']) && ! empty($validated['rd'])) {
            $validated['espesor_pulg'] = round((float) $validated['diametro_exterior_pulg'] / (float) $validated['rd'], 6);
        }

        $validated['material'] = $validated['material'] ?: 'PE-100';

        return $validated;
    }

    private function sincronizarEspecificacionProducto(int $productoId, int $especificacionId): void
    {
        DB::table('tblproductos')
            ->where('id', $productoId)
            ->update([
                'id_especificacion' => $especificacionId,
                'updated_at' => now(),
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Productos>
     */
    private function productosTuboCatalogo()
    {
        return Productos::query()
            ->where(function ($q) {
                $q->where('id_categoria', 50)
                    ->orWhere('sku', 'like', 'TUB%')
                    ->orWhereIn('id', ProductoTuboEspecificacion::query()->select('producto_id')->distinct());
            })
            ->orderBy('sku')
            ->get(['id', 'sku', 'nombre']);
    }

    /**
     * @return array<string, mixed>
     */
    private function filtrosRedirect(Request $request): array
    {
        return array_filter([
            'producto_id' => $request->input('filtro_producto_id'),
            'diametro_nominal' => $request->input('filtro_diametro_nominal'),
            'psi' => $request->input('filtro_psi'),
            'buscar' => $request->input('filtro_buscar'),
            'estatus' => $request->input('filtro_estatus'),
        ], fn ($v) => $v !== null && $v !== '');
    }
}
