<?php

namespace App\Http\Controllers;

use App\Models\ProductoConexionEspecificacion;
use App\Models\ProductoFlangeEspecificacion;
use App\Models\ProductoTuboEspecificacion;
use App\Models\Productos;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\UnidadMedida;
use App\Traits\MenuTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecetasController extends Controller
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

        $query = Receta::with('producto:id,sku,nombre')
            ->withCount('detalles')
            ->orderByDesc('id');

        if ($request->filled('producto_id')) {
            $query->where('producto_id', (int) $request->input('producto_id'));
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->input('estatus'));
        }

        $recetas = $query->get();

        $productosTerminados = Productos::query()
            ->where(function ($q) {
                $q->whereIn('id', ProductoTuboEspecificacion::query()->select('producto_id')->distinct())
                    ->orWhereIn('id', ProductoFlangeEspecificacion::query()->select('producto_id')->distinct())
                    ->orWhereIn('id', ProductoConexionEspecificacion::query()->select('producto_id')->distinct())
                    ->orWhereIn('tipo_proceso', [
                        Productos::TIPO_PROCESO_FLANGE,
                        Productos::TIPO_PROCESO_CONEXION,
                    ]);
            })
            ->orderByRaw("CASE
                WHEN tipo_proceso = 'TUBO' OR tipo_proceso IS NULL OR tipo_proceso = '' THEN 1
                WHEN tipo_proceso = 'FLANGE' THEN 2
                WHEN tipo_proceso = 'CONEXION' THEN 3
                ELSE 4 END")
            ->orderBy('sku')
            ->orderBy('nombre')
            ->get(['id', 'sku', 'nombre', 'tipo_proceso']);

        $flangeIds = ProductoFlangeEspecificacion::query()->select('producto_id')->distinct()->pluck('producto_id')->flip();
        $conexionIds = ProductoConexionEspecificacion::query()->select('producto_id')->distinct()->pluck('producto_id')->flip();
        $productosTerminados->each(function (Productos $producto) use ($flangeIds, $conexionIds) {
            $tipo = strtoupper(trim((string) ($producto->tipo_proceso ?? '')));
            if ($tipo === '' || $tipo === Productos::TIPO_PROCESO_TUBO) {
                if ($conexionIds->has($producto->id)) {
                    $tipo = Productos::TIPO_PROCESO_CONEXION;
                } elseif ($flangeIds->has($producto->id)) {
                    $tipo = Productos::TIPO_PROCESO_FLANGE;
                } else {
                    $tipo = Productos::TIPO_PROCESO_TUBO;
                }
            }
            $producto->setAttribute('tipo_etiqueta', $tipo);
        });

        // Compatibilidad con vistas que aún usan $productosTubo
        $productosTubo = $productosTerminados;

        // Si vienen desde cotización/pedido a dar de alta, asegurar el producto en el select.
        $productoAltaId = (int) $request->input('producto_id', 0);
        if ($productoAltaId > 0 && !$productosTerminados->contains('id', $productoAltaId)) {
            $extra = Productos::query()
                ->where('id', $productoAltaId)
                ->first(['id', 'sku', 'nombre', 'tipo_proceso']);
            if ($extra) {
                $tipo = strtoupper(trim((string) ($extra->tipo_proceso ?? '')));
                if ($tipo === '') {
                    $tipo = Productos::TIPO_PROCESO_TUBO;
                }
                $extra->setAttribute('tipo_etiqueta', $tipo);
                $productosTerminados = $productosTerminados->prepend($extra)->values();
                $productosTubo = $productosTerminados;
            }
        }

        $productosMp = Productos::orderBy('nombre')->get(['id', 'sku', 'nombre', 'id_unidad_medida']);
        $unidades = UnidadMedida::orderBy('nombre')->get(['id', 'nombre', 'abreviacion']);

        return view('Produccion.recetas', compact(
            'varpantallas',
            'varsubmenus',
            'recetas',
            'productosTerminados',
            'productosTubo',
            'productosMp',
            'unidades'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:tblproductos,id'],
            'version' => ['nullable', 'string', 'max:20'],
            'estatus' => ['required', Rule::in(['ACTIVA', 'INACTIVA'])],
            'observaciones' => ['nullable', 'string'],
            'componentes' => ['required', 'array', 'min:1'],
            'componentes.*.producto_mp_id' => ['required', 'integer', 'exists:tblproductos,id', 'distinct'],
            'componentes.*.cantidad' => ['required', 'numeric', 'min:0.0001'],
            'componentes.*.unidad_id' => ['nullable', 'integer', 'exists:tblunidadesmedida,id'],
            'componentes.*.porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'componentes.required' => 'Agregue al menos un producto (materia prima) a la receta.',
            'componentes.min' => 'Agregue al menos un producto (materia prima) a la receta.',
            'componentes.*.producto_mp_id.distinct' => 'No repita el mismo producto en la receta.',
        ]);

        foreach ($validated['componentes'] as $index => $componente) {
            if ((int) $componente['producto_mp_id'] === (int) $validated['producto_id']) {
                throw ValidationException::withMessages([
                    "componentes.{$index}.producto_mp_id" => 'El producto terminado no puede ser materia prima de su propia receta.',
                ]);
            }
        }

        if (!$this->productoPuedeTenerReceta((int) $validated['producto_id'])) {
            throw ValidationException::withMessages([
                'producto_id' => 'Seleccione un tubo, flange o conexión fabricable (con especificación o tipo de proceso).',
            ]);
        }

        $receta = DB::transaction(function () use ($validated) {
            if ($validated['estatus'] === 'ACTIVA') {
                Receta::where('producto_id', $validated['producto_id'])
                    ->where('estatus', 'ACTIVA')
                    ->update(['estatus' => 'INACTIVA']);
            }

            $receta = Receta::create([
                'producto_id' => $validated['producto_id'],
                'version' => $validated['version'] ?? null,
                'estatus' => $validated['estatus'],
                'observaciones' => $validated['observaciones'] ?? null,
                'created_at' => now(),
            ]);

            foreach ($validated['componentes'] as $componente) {
                $unidadId = $componente['unidad_id'] ?? Productos::where('id', $componente['producto_mp_id'])
                    ->value('id_unidad_medida');

                RecetaDetalle::create([
                    'receta_id' => $receta->id,
                    'producto_mp_id' => $componente['producto_mp_id'],
                    'cantidad' => $componente['cantidad'],
                    'unidad_id' => $unidadId,
                    'porcentaje' => $componente['porcentaje'] ?? null,
                ]);
            }

            return $receta;
        });

        return redirect()
            ->route('produccion.recetas.detalle', $receta->id)
            ->with('success', 'Receta guardada con ' . count($validated['componentes']) . ' componente(s).');
    }

    public function detalle(int $id): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $receta = Receta::with(['producto', 'detalles.productoMp', 'detalles.unidad'])
            ->findOrFail($id);

        $productosMp = Productos::orderBy('nombre')->get(['id', 'sku', 'nombre', 'id_unidad_medida']);
        $unidades = UnidadMedida::orderBy('nombre')->get(['id', 'nombre', 'abreviacion']);

        return view('Produccion.receta_detalle', compact(
            'varpantallas',
            'varsubmenus',
            'receta',
            'productosMp',
            'unidades'
        ));
    }

    public function actualizar(Request $request, int $id): RedirectResponse
    {
        $receta = Receta::findOrFail($id);

        $validated = $request->validate([
            'version' => ['nullable', 'string', 'max:20'],
            'estatus' => ['required', Rule::in(['ACTIVA', 'INACTIVA'])],
            'observaciones' => ['nullable', 'string'],
        ]);

        if ($validated['estatus'] === 'ACTIVA') {
            Receta::where('producto_id', $receta->producto_id)
                ->where('id', '!=', $receta->id)
                ->where('estatus', 'ACTIVA')
                ->update(['estatus' => 'INACTIVA']);
        }

        $receta->update($validated);

        return redirect()
            ->route('produccion.recetas.detalle', $receta->id)
            ->with('success', 'Receta actualizada.');
    }

    public function storeDetalle(Request $request, int $id): RedirectResponse
    {
        $receta = Receta::findOrFail($id);

        $validated = $request->validate([
            'producto_mp_id' => ['required', 'integer', 'exists:tblproductos,id'],
            'cantidad' => ['required', 'numeric', 'min:0'],
            'unidad_id' => ['nullable', 'integer', 'exists:tblunidadesmedida,id'],
            'porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if ((int) $validated['producto_mp_id'] === (int) $receta->producto_id) {
            throw ValidationException::withMessages([
                'producto_mp_id' => 'El producto terminado no puede ser materia prima de su propia receta.',
            ]);
        }

        $duplicado = RecetaDetalle::where('receta_id', $receta->id)
            ->where('producto_mp_id', $validated['producto_mp_id'])
            ->exists();

        if ($duplicado) {
            throw ValidationException::withMessages([
                'producto_mp_id' => 'Ese producto ya está en la receta.',
            ]);
        }

        if (empty($validated['unidad_id'])) {
            $validated['unidad_id'] = Productos::where('id', $validated['producto_mp_id'])
                ->value('id_unidad_medida');
        }

        RecetaDetalle::create(array_merge($validated, [
            'receta_id' => $receta->id,
        ]));

        return redirect()
            ->route('produccion.recetas.detalle', $receta->id)
            ->with('success', 'Componente agregado a la receta.');
    }

    public function actualizarDetalle(Request $request, int $id, int $detalleId): RedirectResponse
    {
        $receta = Receta::findOrFail($id);
        $detalle = RecetaDetalle::where('receta_id', $receta->id)->findOrFail($detalleId);

        $validated = $request->validate([
            'cantidad' => ['required', 'numeric', 'min:0'],
            'unidad_id' => ['nullable', 'integer', 'exists:tblunidadesmedida,id'],
            'porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $detalle->update($validated);

        return redirect()
            ->route('produccion.recetas.detalle', $receta->id)
            ->with('success', 'Componente actualizado.');
    }

    public function eliminarDetalle(int $id, int $detalleId): RedirectResponse
    {
        $receta = Receta::findOrFail($id);
        RecetaDetalle::where('receta_id', $receta->id)->where('id', $detalleId)->delete();

        return redirect()
            ->route('produccion.recetas.detalle', $receta->id)
            ->with('success', 'Componente eliminado.');
    }

    public function recetaProductoJson(int $productoId): JsonResponse
    {
        Productos::findOrFail($productoId);

        $receta = Receta::activaParaProducto($productoId);

        if (!$receta) {
            return response()->json(['receta' => null]);
        }

        return response()->json([
            'receta' => [
                'id' => $receta->id,
                'version' => $receta->version,
                'observaciones' => $receta->observaciones,
                'componentes' => $receta->detalles->map(function (RecetaDetalle $linea) {
                    return [
                        'id' => $linea->id,
                        'producto_mp_id' => $linea->producto_mp_id,
                        'sku' => $linea->productoMp?->sku,
                        'nombre' => $linea->productoMp?->nombre,
                        'cantidad' => $linea->cantidad,
                        'unidad' => $linea->unidad?->nombre ?? $linea->unidad?->abreviacion,
                        'porcentaje' => $linea->porcentaje,
                    ];
                })->values(),
            ],
        ]);
    }

    /** Tubo, flange o conexión con especificación o tipo de proceso fabricable. */
    private function productoPuedeTenerReceta(int $productoId): bool
    {
        if (ProductoTuboEspecificacion::where('producto_id', $productoId)->exists()
            || ProductoFlangeEspecificacion::where('producto_id', $productoId)->exists()
            || ProductoConexionEspecificacion::where('producto_id', $productoId)->exists()) {
            return true;
        }

        $tipo = Productos::where('id', $productoId)->value('tipo_proceso');

        return in_array($tipo, [
            Productos::TIPO_PROCESO_FLANGE,
            Productos::TIPO_PROCESO_CONEXION,
        ], true);
    }
}
