<?php

namespace App\Http\Controllers;

use App\Models\LicParamPlantilla;
use App\Models\LicParamTipo;
use App\Models\Licitacion;
use App\Services\LicitacionParametroService;
use App\Traits\MenuTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LicitacionParametroController extends Controller
{
    use MenuTrait;

    protected LicitacionParametroService $service;

    public function __construct(LicitacionParametroService $service)
    {
        $this->middleware('auth');
        $this->service = $service;
    }

    protected function menuData(): array
    {
        return [
            'varpantallas' => $this->Traermenuenc(),
            'varsubmenus' => $this->Traermenudet(),
        ];
    }

    public function index()
    {
        $tipos = LicParamTipo::query()
            ->withCount(['plantilla as parametros_count' => fn ($q) => $q->where('activo', true)])
            ->with(['plantilla' => fn ($q) => $q->where('activo', true)->orderBy('orden')])
            ->orderBy('nombre')
            ->get();

        return view('Licitacion.parametros.index', array_merge($this->menuData(), [
            'tipos' => $tipos,
            'sentidos' => LicParamPlantilla::SENTIDOS,
            'tiposDato' => LicParamPlantilla::TIPOS_DATO,
        ]));
    }

    public function storeTipo(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:120',
            'descripcion' => 'nullable|string|max:500',
        ]);

        LicParamTipo::query()->create([
            'nombre' => trim($data['nombre']),
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => true,
        ]);

        return back()->with('success', 'Tipo / familia creado.');
    }

    public function updateTipo(Request $request, int $id)
    {
        $tipo = LicParamTipo::query()->findOrFail($id);
        $data = $request->validate([
            'nombre' => 'required|string|max:120',
            'descripcion' => 'nullable|string|max:500',
            'activo' => 'nullable|boolean',
        ]);

        $tipo->update([
            'nombre' => trim($data['nombre']),
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => $request->boolean('activo', true),
        ]);

        return back()->with('success', 'Tipo actualizado.');
    }

    public function storeParametro(Request $request, int $tipoId)
    {
        $tipo = LicParamTipo::query()->findOrFail($tipoId);
        $data = $request->validate([
            'nombre' => 'required|string|max:160',
            'clave' => 'nullable|string|max:80',
            'peso_pct' => 'required|numeric|min:0|max:100',
            'sentido' => 'required|in:menor_mejor,mayor_mejor',
            'tipo_dato' => 'required|in:numero,porcentaje,moneda,dias',
            'orden' => 'nullable|integer|min:1',
        ]);

        $orden = (int) ($data['orden'] ?? ((int) $tipo->plantilla()->max('orden') + 1));

        LicParamPlantilla::query()->create([
            'tipo_id' => $tipo->id,
            'nombre' => trim($data['nombre']),
            'clave' => $data['clave'] ?? null,
            'peso_pct' => $data['peso_pct'],
            'sentido' => $data['sentido'],
            'tipo_dato' => $data['tipo_dato'],
            'obligatorio' => true,
            'orden' => $orden,
            'activo' => true,
        ]);

        try {
            $this->service->assertPlantillaTipoCien($tipo->id);
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Ajusta los pesos hasta 100%.';

            return back()->with('warning', $msg);
        }

        return back()->with('success', 'Parámetro agregado al tipo.');
    }

    public function updateParametro(Request $request, int $paramId)
    {
        $param = LicParamPlantilla::query()->findOrFail($paramId);
        $data = $request->validate([
            'nombre' => 'required|string|max:160',
            'clave' => 'nullable|string|max:80',
            'peso_pct' => 'required|numeric|min:0|max:100',
            'sentido' => 'required|in:menor_mejor,mayor_mejor',
            'tipo_dato' => 'required|in:numero,porcentaje,moneda,dias',
            'orden' => 'nullable|integer|min:1',
            'activo' => 'nullable|boolean',
        ]);

        $param->update([
            'nombre' => trim($data['nombre']),
            'clave' => $data['clave'] ?? null,
            'peso_pct' => $data['peso_pct'],
            'sentido' => $data['sentido'],
            'tipo_dato' => $data['tipo_dato'],
            'orden' => (int) ($data['orden'] ?? $param->orden),
            'activo' => $request->has('activo') ? $request->boolean('activo') : $param->activo,
        ]);

        try {
            $this->service->assertPlantillaTipoCien((int) $param->tipo_id);
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Ajusta los pesos hasta 100%.';

            return back()->with('warning', $msg);
        }

        return back()->with('success', 'Parámetro actualizado.');
    }

    public function destroyParametro(int $paramId)
    {
        $param = LicParamPlantilla::query()->findOrFail($paramId);
        $tipoId = (int) $param->tipo_id;
        $param->delete();

        try {
            $this->service->assertPlantillaTipoCien($tipoId);
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: '';

            return back()->with('warning', 'Parámetro eliminado. '.$msg);
        }

        return back()->with('success', 'Parámetro eliminado.');
    }

    public function syncLicitacion(Request $request, int $licitacionId)
    {
        $lic = Licitacion::query()->findOrFail($licitacionId);
        $data = $request->validate([
            'parametros' => 'required|array|min:1',
            'parametros.*.id' => 'nullable|integer',
            'parametros.*.nombre' => 'required|string|max:160',
            'parametros.*.peso_pct' => 'required|numeric|min:0|max:100',
            'parametros.*.sentido' => 'required|in:menor_mejor,mayor_mejor',
            'parametros.*.tipo_dato' => 'required|in:numero,porcentaje,moneda,dias',
            'parametros.*.orden' => 'nullable|integer|min:1',
            'parametros.*.obligatorio' => 'nullable|boolean',
        ]);

        try {
            $this->service->syncParametrosLicitacion($lic, $data['parametros']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', 'Parámetros de la licitación actualizados.');
    }

    public function aplicarTipo(Request $request, int $licitacionId)
    {
        $lic = Licitacion::query()->findOrFail($licitacionId);
        $data = $request->validate([
            'tipo_param_id' => 'required|integer|exists:tbl_lic_param_tipos,id',
        ]);

        $this->service->clonarPlantillaALicitacion($lic, (int) $data['tipo_param_id']);

        return back()->with('success', 'Se aplicó la plantilla del tipo a esta licitación (puedes ajustarla).');
    }

    public function guardarScores(Request $request, int $licitacionId)
    {
        $lic = Licitacion::query()->findOrFail($licitacionId);

        $scoresIn = $request->input('scores', []);
        if (is_array($scoresIn)) {
            foreach ($scoresIn as $k => $row) {
                if (is_array($row) && array_key_exists('valor', $row) && $row['valor'] === '') {
                    $scoresIn[$k]['valor'] = null;
                }
            }
            $request->merge(['scores' => $scoresIn]);
        }

        $data = $request->validate([
            'scores' => 'required|array',
            'scores.*.parametro_id' => 'required|integer',
            'scores.*.proveedor_id' => 'required|integer',
            'scores.*.valor' => 'nullable|numeric',
            'scores.*.comentario' => 'nullable|string|max:500',
        ]);

        $this->service->sincronizarPrecioDesdeCotizaciones($lic);
        $this->service->guardarYNormalizarScores($lic, array_values($data['scores']));
        // Reaplica cotizaciones sobre Precio por si el form envió un valor manual viejo
        if ($this->service->sincronizarPrecioDesdeCotizaciones($lic)) {
            $this->service->recalcularNormalizacion($lic);
        }

        return redirect()
            ->route('licitaciones.comparar', $licitacionId)
            ->with('success', 'Evaluación guardada y ranking actualizado.');
    }
}
