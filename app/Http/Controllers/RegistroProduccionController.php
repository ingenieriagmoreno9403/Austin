<?php

namespace App\Http\Controllers;

use App\Models\CausaParo;
use App\Models\CostoPeadMensual;
use App\Models\OrdenProduccion;
use App\Models\ParoProduccion;
use App\Models\RegistroProduccion;
use App\Services\RegistroProduccionCalculator;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistroProduccionController extends Controller
{
    use MenuTrait;
    use SistemasTraits;

    public function __construct(
        private readonly RegistroProduccionCalculator $calculator
    ) {
        $this->middleware('auth');
    }

    public function storeRegistro(Request $request, int $id): RedirectResponse
    {
        $orden = OrdenProduccion::with(['maquina', 'detalles.salidas'])->findOrFail($id);

        $validated = $request->validate([
            'hora_inicio' => ['nullable', 'date'],
            'hora_fin' => ['nullable', 'date', 'after_or_equal:hora_inicio'],
            't_programado_h' => ['nullable', 'numeric', 'min:0'],
            't_arranque_h' => ['nullable', 'numeric', 'min:0'],
            'mat_inicial_kg' => ['nullable', 'numeric', 'min:0'],
            'mat_reciclado_kg' => ['nullable', 'numeric', 'min:0'],
            'aditivos_kg' => ['nullable', 'numeric', 'min:0'],
            'mat_sobrante_kg' => ['nullable', 'numeric', 'min:0'],
            'prod_bueno_kg' => ['nullable', 'numeric', 'min:0'],
            'prod_defectuoso_kg' => ['nullable', 'numeric', 'min:0'],
            'kwh' => ['nullable', 'numeric', 'min:0'],
            'costo_pead_kg' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $calc = $this->calculator->calcular($orden, $validated);

        RegistroProduccion::updateOrCreate(
            ['orden_id' => $orden->id],
            array_merge($calc, ['updated_by' => auth()->id()])
        );

        return redirect()
            ->route('produccion.ordenes.detalle', $orden->id)
            ->with('success', 'Registro de producción OEE guardado.');
    }

    public function storeParo(Request $request, int $id): RedirectResponse
    {
        $orden = OrdenProduccion::findOrFail($id);

        $validated = $request->validate([
            'causa_paro_id' => ['required', 'integer', 'exists:tbl_causas_paro,id'],
            'fecha' => ['required', 'date'],
            'hora_inicio' => ['required', 'date'],
            'hora_fin' => ['required', 'date', 'after:hora_inicio'],
            'comentario' => ['nullable', 'string', 'max:500'],
        ]);

        $inicio = Carbon::parse($validated['hora_inicio']);
        $fin = Carbon::parse($validated['hora_fin']);
        $duracion = round($inicio->floatDiffInHours($fin), 3);

        DB::transaction(function () use ($orden, $validated, $inicio, $fin, $duracion) {
            ParoProduccion::create([
                'orden_id' => $orden->id,
                'maquina_id' => $orden->maquina_id,
                'causa_paro_id' => $validated['causa_paro_id'],
                'fecha' => $validated['fecha'],
                'hora_inicio' => $inicio,
                'hora_fin' => $fin,
                'duracion_h' => $duracion,
                'comentario' => $validated['comentario'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $this->calculator->recalcularYGuardar(
                $orden->fresh(['maquina', 'detalles.salidas']),
                RegistroProduccion::where('orden_id', $orden->id)->first()
            );
        });

        return redirect()
            ->route('produccion.ordenes.detalle', $orden->id)
            ->with('success', 'Paro registrado (' . number_format($duracion, 2) . ' h).');
    }

    public function destroyParo(int $id, int $paroId): RedirectResponse
    {
        $orden = OrdenProduccion::findOrFail($id);
        $paro = ParoProduccion::where('orden_id', $orden->id)->findOrFail($paroId);
        $paro->delete();

        $this->calculator->recalcularYGuardar(
            $orden->fresh(['maquina', 'detalles.salidas']),
            RegistroProduccion::where('orden_id', $orden->id)->first()
        );

        return redirect()
            ->route('produccion.ordenes.detalle', $orden->id)
            ->with('success', 'Paro eliminado y OEE recalculado.');
    }

    public function costoPeadIndex(): View
    {
        $this->assertPuedeCostoPead();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $costos = CostoPeadMensual::query()->orderByDesc('mes')->get();

        return view('Produccion.costo_pead', compact(
            'varpantallas',
            'varsubmenus',
            'costos'
        ));
    }

    public function storeCostoPead(Request $request): RedirectResponse
    {
        $this->assertPuedeCostoPead();

        $validated = $request->validate([
            'mes' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'costo_kg' => ['required', 'numeric', 'min:0'],
            'proveedor' => ['nullable', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        CostoPeadMensual::updateOrCreate(
            ['mes' => $validated['mes']],
            [
                'costo_kg' => $validated['costo_kg'],
                'proveedor' => $validated['proveedor'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
            ]
        );

        return redirect()
            ->route('produccion.costo_pead')
            ->with('success', 'Costo PEAD del mes ' . $validated['mes'] . ' guardado.');
    }

    public function updateCostoPead(Request $request, int $id): RedirectResponse
    {
        $this->assertPuedeCostoPead();
        $costo = CostoPeadMensual::findOrFail($id);

        $validated = $request->validate([
            'costo_kg' => ['required', 'numeric', 'min:0'],
            'proveedor' => ['nullable', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $costo->update($validated);

        return redirect()
            ->route('produccion.costo_pead')
            ->with('success', 'Costo PEAD actualizado.');
    }

    private function assertPuedeCostoPead(): void
    {
        $ok = $this->forpermisos('ver_costo_pead') === 'ver_costo_pead'
            || $this->forpermisos('ver_produccion') === 'ver_produccion';
        if (!$ok) {
            abort(403, 'No tiene permiso para costo PEAD.');
        }
    }
}
