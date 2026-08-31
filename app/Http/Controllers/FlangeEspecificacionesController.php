<?php

namespace App\Http\Controllers;

use App\Models\ProductoFlangeEspecificacion;
use App\Models\Productos;
use App\Traits\MenuTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FlangeEspecificacionesController extends Controller
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

        $query = ProductoFlangeEspecificacion::with('producto')
            ->orderByDesc('id');

        if ($request->input('estatus') === 'INACTIVO') {
            $query->where('estatus', 'INACTIVO');
        } elseif ($request->input('estatus') !== 'TODOS') {
            $query->where('estatus', 'ACTIVO');
        }

        if ($productoId > 0) {
            $query->where('producto_id', $productoId);
        }

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('diametro_nominal', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%")
                    ->orWhereHas('producto', function ($pq) use ($buscar) {
                        $pq->where('sku', 'like', "%{$buscar}%")
                            ->orWhere('nombre', 'like', "%{$buscar}%");
                    });
            });
        }

        $especificaciones = $query->paginate(50)->withQueryString();
        $totalEspecificaciones = ProductoFlangeEspecificacion::count();

        $productos = Productos::query()
            ->where(function ($q) {
                $q->where('tipo_proceso', Productos::TIPO_PROCESO_FLANGE)
                    ->orWhereIn('id', ProductoFlangeEspecificacion::query()->select('producto_id')->distinct());
            })
            ->orderBy('nombre')
            ->get(['id', 'sku', 'nombre', 'tipo_proceso']);

        $productosCatalogo = Productos::query()
            ->orderBy('nombre')
            ->limit(800)
            ->get(['id', 'sku', 'nombre']);

        return view('Produccion.especificaciones_flange', compact(
            'varpantallas',
            'varsubmenus',
            'especificaciones',
            'productos',
            'productosCatalogo',
            'productoId',
            'totalEspecificaciones',
            'buscar'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validar($request);

        DB::transaction(function () use ($validated) {
            ProductoFlangeEspecificacion::create($validated);
            Productos::where('id', $validated['producto_id'])
                ->update(['tipo_proceso' => Productos::TIPO_PROCESO_FLANGE]);
        });

        return redirect()
            ->route('produccion.especificaciones.flange', ['producto_id' => $validated['producto_id']])
            ->with('success', 'Especificación flange registrada. Ruta: '
                . ProductoFlangeEspecificacion::resolverRuta($validated['diametro_nominal'], $validated['rd'] ?? null));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $spec = ProductoFlangeEspecificacion::findOrFail($id);
        $validated = $this->validar($request, $spec->id);

        DB::transaction(function () use ($spec, $validated) {
            $spec->update($validated);
            if (($validated['estatus'] ?? '') === 'ACTIVO') {
                Productos::where('id', $validated['producto_id'])
                    ->update(['tipo_proceso' => Productos::TIPO_PROCESO_FLANGE]);
            }
        });

        return redirect()
            ->route('produccion.especificaciones.flange', ['producto_id' => $validated['producto_id']])
            ->with('success', 'Especificación flange actualizada.');
    }

    private function validar(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'producto_id' => ['required', 'integer', 'exists:tblproductos,id'],
            'diametro_nominal' => ['required', 'string', 'max:50'],
            'rd' => ['nullable', 'numeric', 'min:0'],
            'peso_kg_pieza' => ['nullable', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estatus' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
        ]);
    }
}
