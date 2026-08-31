<?php

namespace App\Http\Controllers;

use App\Models\Descuento;
use App\Models\GrupoSocio;
use App\Models\MontoSocio;
use App\Models\PagoSocioDet;
use App\Models\PagoSocioEnc;
use App\Models\Socio;
use App\Models\TipoSocio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GestionAlumnosSocioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = Socio::query()
            ->with(['tipoSocio:id,tipo_socio', 'grupo:id,tipo_socio', 'descuento:id,tipo_socio,porcentaje_des', 'titular:id,numero_socio,nombre,ap_paterno,ap_materno'])
            ->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('numero_socio', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%")
                    ->orWhere('ap_paterno', 'like', "%{$q}%")
                    ->orWhere('ap_materno', 'like', "%{$q}%")
                    ->orWhere('empresa', 'like', "%{$q}%")
                    ->orWhere('telefono', 'like', "%{$q}%")
                    ->orWhere('status', 'like', "%{$q}%");

                if (Schema::hasColumn('tblsocios', 'correo')) {
                    $w->orWhere('correo', 'like', "%{$q}%");
                }
                if (Schema::hasColumn('tblsocios', 'domicilio')) {
                    $w->orWhere('domicilio', 'like', "%{$q}%");
                }
                if (Schema::hasColumn('tblsocios', 'cuota_periodicidad')) {
                    $w->orWhere('cuota_periodicidad', 'like', "%{$q}%");
                }
                if (Schema::hasColumn('tblsocios', 'fecha_facturacion')) {
                    $w->orWhere('fecha_facturacion', 'like', "%{$q}%");
                }
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function show(int $id): JsonResponse
    {
        $socio = Socio::with(['tipoSocio:id,tipo_socio', 'grupo:id,tipo_socio', 'descuento:id,tipo_socio,porcentaje_des', 'titular:id,numero_socio,nombre,ap_paterno,ap_materno'])
            ->findOrFail($id);

        return response()->json(['data' => $socio]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validar($request, null, true);
        $this->normalizarModoTitularOAllegado($data);
        $data['status'] = 'pendiente';
        $data['created_by'] = optional(auth()->user())->name;
        $data['updated_by'] = optional(auth()->user())->name;
        $data['foto_path'] = $this->guardarFotoSocio($request, $data);
        unset($data['foto_socio']);

        $socio = Socio::create($data);

        return response()->json([
            'message' => 'Socio registrado correctamente.',
            'data' => $socio->fresh(),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $socio = Socio::findOrFail($id);
        $data = $this->validar($request, $id, false);
        $this->normalizarModoTitularOAllegado($data);
        $data['updated_by'] = optional(auth()->user())->name;
        if ($request->hasFile('foto_socio')) {
            $data['foto_path'] = $this->guardarFotoSocio($request, $data, $socio->foto_path);
        }
        unset($data['foto_socio']);

        $socio->update($data);

        return response()->json([
            'message' => 'Socio actualizado correctamente.',
            'data' => $socio->fresh(),
        ]);
    }

    public function tiposSocio(): JsonResponse
    {
        return response()->json(['data' => TipoSocio::query()->orderBy('tipo_socio')->get()]);
    }

    public function gruposSocio(): JsonResponse
    {
        return response()->json(['data' => GrupoSocio::query()->orderBy('tipo_socio')->get()]);
    }

    public function descuentos(): JsonResponse
    {
        return response()->json(['data' => Descuento::query()->orderBy('tipo_socio')->get()]);
    }

    public function importarMasivo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:15360'],
            'actualizar_existentes' => ['nullable', 'boolean'],
        ]);

        $actualizarExistentes = $request->boolean('actualizar_existentes', true);
        $spreadsheet = IOFactory::load($validated['archivo']->getRealPath());
        $sheet = $this->resolverHojaDirectorioSocios($spreadsheet);
        $tipoTitular = TipoSocio::query()->whereRaw('LOWER(tipo_socio) = ?', ['titular'])->first();
        if (! $tipoTitular) {
            return response()->json(['message' => 'No existe el tipo de socio "Titular" en catálogo.'], 422);
        }

        $headerRow = $this->detectarFilaEncabezadoSocios($sheet);
        $mapaColumnas = $headerRow > 0
            ? $this->mapearColumnasDesdeEncabezado($sheet, $headerRow)
            : $this->mapearColumnasLayoutAlterno();

        $usuario = optional(auth()->user())->name;
        $creados = 0;
        $actualizados = 0;
        $omitidos = 0;
        $errores = [];
        $inicioDatos = $headerRow > 0 ? $headerRow + 1 : 1;
        $maxRow = (int) $sheet->getHighestRow();

        for ($fila = $inicioDatos; $fila <= $maxRow; $fila++) {
            $registro = $this->extraerRegistroSocioDesdeFila($sheet, $fila, $mapaColumnas);
            if ($registro === null) {
                continue;
            }

            $numeroSocio = strtoupper(trim((string) ($registro['numero_socio'] ?? '')));
            if ($numeroSocio === '' || ! preg_match('/^[A-Z0-9\-]+$/i', $numeroSocio)) {
                $omitidos++;
                continue;
            }

            try {
                $nombres = $this->parsearNombreContacto((string) ($registro['contacto'] ?? ''));
                $empresa = trim((string) ($registro['empresa'] ?? ''));
                if ($nombres['nombre'] === '' && $empresa !== '') {
                    $nombres['nombre'] = $empresa;
                }
                if ($nombres['nombre'] === '') {
                    $nombres['nombre'] = 'SIN NOMBRE';
                }

                $payload = [
                    'id_titular' => null,
                    'numero_socio' => $numeroSocio,
                    'numero_dependiente' => null,
                    'id_tipo_socio' => $tipoTitular->id,
                    'nombre' => mb_substr($nombres['nombre'], 0, 120),
                    'segund_nom' => $nombres['segund_nom'] ? mb_substr($nombres['segund_nom'], 0, 120) : null,
                    'ap_paterno' => $nombres['ap_paterno'] ? mb_substr($nombres['ap_paterno'], 0, 120) : null,
                    'ap_materno' => $nombres['ap_materno'] ? mb_substr($nombres['ap_materno'], 0, 120) : null,
                    'telefono' => $this->normalizarTelefono($registro['telefono'] ?? null),
                    'empresa' => $empresa !== '' ? mb_substr($empresa, 0, 200) : null,
                    'status' => 'activo',
                    'updated_by' => $usuario,
                ];

                if (Schema::hasColumn('tblsocios', 'correo')) {
                    $payload['correo'] = $this->normalizarCorreo($registro['correo'] ?? null);
                }
                if (Schema::hasColumn('tblsocios', 'domicilio')) {
                    $payload['domicilio'] = $this->valorTextoLargo($registro['domicilio'] ?? null);
                }
                if (Schema::hasColumn('tblsocios', 'cuota_periodicidad')) {
                    $payload['cuota_periodicidad'] = $this->valorTextoCorto($registro['cuota'] ?? null, 80);
                }
                if (Schema::hasColumn('tblsocios', 'fecha_facturacion')) {
                    $payload['fecha_facturacion'] = $this->valorTextoCorto($registro['fecha_facturacion'] ?? null, 120);
                }

                $existente = Socio::query()
                    ->where('numero_socio', $numeroSocio)
                    ->whereNull('numero_dependiente')
                    ->first();

                if ($existente) {
                    if (! $actualizarExistentes) {
                        $omitidos++;
                        continue;
                    }
                    $existente->update($payload);
                    $actualizados++;
                    continue;
                }

                $payload['created_by'] = $usuario;
                Socio::create($payload);
                $creados++;
            } catch (\Throwable $e) {
                $errores[] = "Fila {$fila} ({$numeroSocio}): " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Importación de socios finalizada.',
            'data' => [
                'hoja' => $sheet->getTitle(),
                'fila_encabezado' => $headerRow,
                'creados' => $creados,
                'actualizados' => $actualizados,
                'omitidos' => $omitidos,
                'errores' => $errores,
            ],
        ]);
    }

    public function siguienteDependiente(int $id): JsonResponse
    {
        $titular = Socio::findOrFail($id);
        $maxActual = Socio::query()
            ->where('numero_socio', $titular->numero_socio)
            ->max('numero_dependiente');

        $siguiente = ((int) $maxActual) + 1;
        if ($siguiente < 1) {
            $siguiente = 1;
        }

        return response()->json([
            'data' => [
                'id_titular' => $titular->id,
                'numero_socio' => $titular->numero_socio,
                'numero_dependiente' => $siguiente,
            ],
        ]);
    }

    public function montoSugerido(int $id, Request $request): JsonResponse
    {
        $socio = Socio::with('descuento:id,porcentaje_des')->findOrFail($id);
        $periodicidad = trim((string) $request->query('periodicidad', 'mensual'));
        $montoBase = $this->resolverMontoBase($socio, $periodicidad);
        $porcentaje = $this->resolverPorcentajeDescuento($socio);
        $montoFinal = max(0, round($montoBase - (($montoBase * $porcentaje) / 100), 2));

        return response()->json([
            'data' => [
                'periodicidad' => $periodicidad,
                'monto_base' => $montoBase,
                'porcentaje_descuento' => $porcentaje,
                'monto_final_periodo' => $montoFinal,
            ],
        ]);
    }

    public function crearPlanPagos(int $id, Request $request): JsonResponse
    {
        $socio = Socio::with('descuento:id,porcentaje_des')->findOrFail($id);
        $planExistente = PagoSocioEnc::query()
            ->where('id_socio', $id)
            ->whereIn('status', ['activo', 'pendiente'])
            ->exists();
        if ($planExistente) {
            return response()->json(['message' => 'El socio ya tiene un plan generado. Debes cerrar/cancelar el actual antes de crear otro.'], 422);
        }

        $validated = $request->validate([
            'periodicidad' => ['required', Rule::in(['quincenal', 'mensual', 'bimestral', 'trimestral', 'semestral', 'anual'])],
            'fecha_inicio' => ['nullable', 'date'],
            'plazos' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $periodicidad = $validated['periodicidad'];
        $fechaInicio = isset($validated['fecha_inicio']) ? Carbon::parse($validated['fecha_inicio']) : Carbon::now();
        $plazos = isset($validated['plazos']) ? (int) $validated['plazos'] : $this->plazosDefault($periodicidad);

        $montoBase = $this->resolverMontoBase($socio, $periodicidad);
        $porcentaje = $this->resolverPorcentajeDescuento($socio);
        $montoFinal = max(0, round($montoBase - (($montoBase * $porcentaje) / 100), 2));
        $montoTotal = round($montoFinal * $plazos, 2);

        $usuario = optional(auth()->user())->name;

        $plan = DB::transaction(function () use ($socio, $periodicidad, $fechaInicio, $plazos, $montoBase, $porcentaje, $montoFinal, $montoTotal, $usuario) {
            $enc = PagoSocioEnc::create([
                'id_socio' => $socio->id,
                'periodicidad' => $periodicidad,
                'fecha_inicio' => $fechaInicio->toDateString(),
                'plazos' => $plazos,
                'monto_base' => $montoBase,
                'porcentaje_descuento' => $porcentaje,
                'monto_final_periodo' => $montoFinal,
                'monto_total_plan' => $montoTotal,
                'status' => 'activo',
                'created_by' => $usuario,
                'updated_by' => $usuario,
            ]);

            for ($i = 1; $i <= $plazos; $i++) {
                PagoSocioDet::create([
                    'id_pago_enc' => $enc->id,
                    'num_plazo' => $i,
                    'fecha_programada' => $this->sumarPeriodicidad($fechaInicio, $periodicidad, $i - 1)->toDateString(),
                    'monto_programado' => $montoFinal,
                    'status' => 'pendiente',
                    'created_by' => $usuario,
                    'updated_by' => $usuario,
                ]);
            }

            return $enc;
        });

        return response()->json([
            'message' => 'Plan de pagos generado correctamente.',
            'data' => $plan->load('detalles'),
        ], 201);
    }

    public function planesPagoSocio(int $id): JsonResponse
    {
        $this->actualizarVencidos($id);

        $planes = PagoSocioEnc::query()
            ->with(['detalles' => function ($q) {
                $q->orderBy('num_plazo');
            }])
            ->where('id_socio', $id)
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $planes]);
    }

    public function pagarDetalle(int $id, int $detalleId, Request $request): JsonResponse
    {
        $detalle = PagoSocioDet::query()
            ->where('id', $detalleId)
            ->whereHas('encabezado', function ($q) use ($id) {
                $q->where('id_socio', $id);
            })
            ->firstOrFail();

        if ($detalle->status === 'pagado') {
            return response()->json(['message' => 'Este plazo ya está pagado.'], 422);
        }

        // Se permite pagar plazos vencidos para regularizar adeudos.

        $validated = $request->validate([
            'fecha_pago' => ['nullable', 'date'],
            'monto_pagado' => ['nullable', 'numeric', 'min:0'],
        ]);

        $fechaPago = $validated['fecha_pago'] ?? Carbon::now()->toDateString();
        $montoPagado = (float) ($validated['monto_pagado'] ?? $detalle->monto_programado);

        $detalle->update([
            'fecha_pago' => $fechaPago,
            'monto_pagado' => $montoPagado,
            'status' => 'pagado',
            'updated_by' => optional(auth()->user())->name,
        ]);

        $this->registrarMovimientoTesoreria(
            $montoPagado,
            'INGRESO',
            'Pago socio #' . $id,
            'Aplicación de pago del plazo ' . $detalle->num_plazo . ' del plan #' . $detalle->id_pago_enc
        );

        return response()->json([
            'message' => 'Pago registrado correctamente.',
            'data' => $detalle->fresh(),
        ]);
    }

    public function cancelarDetalle(int $id, int $detalleId): JsonResponse
    {
        $detalle = PagoSocioDet::query()
            ->where('id', $detalleId)
            ->whereHas('encabezado', function ($q) use ($id) {
                $q->where('id_socio', $id);
            })
            ->firstOrFail();

        if ($detalle->status !== 'pagado') {
            return response()->json(['message' => 'Solo se pueden cancelar pagos ya pagados.'], 422);
        }

        $ultimoPagado = PagoSocioDet::query()
            ->where('id_pago_enc', $detalle->id_pago_enc)
            ->where('status', 'pagado')
            ->orderByDesc('num_plazo')
            ->first();

        if (!$ultimoPagado || (int) $ultimoPagado->id !== (int) $detalle->id) {
            return response()->json([
                'message' => 'No puedes cancelar este pago todavía. Debes cancelar primero el último pago aplicado.',
            ], 422);
        }

        $detalle->update([
            'status' => 'cancelado',
            'updated_by' => optional(auth()->user())->name,
        ]);

        $montoCancelado = (float) ($detalle->monto_pagado ?? $detalle->monto_programado ?? 0);
        $usuario = optional(auth()->user())->name;

        if (Schema::hasTable('tblpagos_socios_cancelados')) {
            DB::table('tblpagos_socios_cancelados')->insert([
                'id_socio' => $id,
                'id_pago_enc' => $detalle->id_pago_enc,
                'id_pago_det' => $detalle->id,
                'num_plazo' => $detalle->num_plazo,
                'monto_cancelado' => $montoCancelado,
                'fecha_programada' => $detalle->fecha_programada,
                'fecha_cancelacion' => Carbon::now()->toDateString(),
                'motivo' => 'Cancelación de pago aplicado',
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => $usuario,
                'updated_by' => $usuario,
            ]);
        }

        $this->registrarMovimientoTesoreria(
            $montoCancelado,
            'GASTO',
            'Cancelación pago socio #' . $id,
            'Cancelación del plazo ' . $detalle->num_plazo . ' del plan #' . $detalle->id_pago_enc
        );

        return response()->json([
            'message' => 'Pago cancelado correctamente.',
            'data' => $detalle->fresh(),
        ]);
    }

    private function validar(Request $request, ?int $id = null, bool $fotoObligatoria = false): array
    {
        $numeroDependiente = $request->input('numero_dependiente');
        $reglaNumeroSocio = Rule::unique('tblsocios', 'numero_socio')
            ->ignore($id)
            ->where(function ($q) use ($numeroDependiente) {
                if ($numeroDependiente === null || $numeroDependiente === '') {
                    $q->whereNull('numero_dependiente');
                } else {
                    $q->where('numero_dependiente', (int) $numeroDependiente);
                }
            });

        return $request->validate([
            'numero_socio' => [
                'required',
                'string',
                'max:60',
                $reglaNumeroSocio,
            ],
            'numero_dependiente' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'id_titular' => ['nullable', 'integer', Rule::exists('tblsocios', 'id')],
            'id_tipo_socio' => ['required', 'integer', Rule::exists('tbltipos_socio', 'id')],
            'nombre' => ['required', 'string', 'max:120'],
            'segund_nom' => ['nullable', 'string', 'max:120'],
            'ap_paterno' => ['nullable', 'string', 'max:120'],
            'ap_materno' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'correo' => ['nullable', 'string', 'max:250'],
            'domicilio' => ['nullable', 'string', 'max:2000'],
            'cuota_periodicidad' => ['nullable', 'string', 'max:80'],
            'fecha_facturacion' => ['nullable', 'string', 'max:120'],
            'edad' => ['nullable', 'integer', 'min:0', 'max:120'],
            'empresa' => ['nullable', 'string', 'max:200'],
            'id_grupo' => ['nullable', 'integer', Rule::exists('grupos_socios', 'id')],
            'id_descuento' => ['nullable', 'integer', Rule::exists('tbldescuentos', 'id')],
            'id_porcentaje_des' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['nullable', Rule::in(['activo', 'pendiente', 'cancelado'])],
            'foto_socio' => [($fotoObligatoria ? 'required' : 'nullable'), 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
    }

    private function resolverPorcentajeDescuento(Socio $socio): float
    {
        if ($socio->id_porcentaje_des !== null) {
            return (float) $socio->id_porcentaje_des;
        }
        if ($socio->descuento && $socio->descuento->porcentaje_des !== null) {
            return (float) $socio->descuento->porcentaje_des;
        }
        return 0.0;
    }

    private function resolverMontoBase(Socio $socio, string $periodicidad): float
    {
        $monto = MontoSocio::query()
            ->where('id_tipo_socio', $socio->id_tipo_socio)
            ->where('periodicidad', $periodicidad)
            ->where('status', 'activo')
            ->where(function ($q) use ($socio) {
                $q->where('id_grupo', $socio->id_grupo)->orWhereNull('id_grupo');
            })
            ->orderByRaw('id_grupo is null')
            ->first();

        return $monto ? (float) $monto->monto : 0.0;
    }

    private function plazosDefault(string $periodicidad): int
    {
        return match ($periodicidad) {
            'quincenal' => 24,
            'mensual' => 12,
            'bimestral' => 6,
            'trimestral' => 4,
            'semestral' => 2,
            'anual' => 1,
            default => 12,
        };
    }

    private function sumarPeriodicidad(Carbon $fechaBase, string $periodicidad, int $indice): Carbon
    {
        return match ($periodicidad) {
            'quincenal' => $fechaBase->copy()->addDays(15 * $indice),
            'mensual' => $fechaBase->copy()->addMonthsNoOverflow($indice),
            'bimestral' => $fechaBase->copy()->addMonthsNoOverflow(2 * $indice),
            'trimestral' => $fechaBase->copy()->addMonthsNoOverflow(3 * $indice),
            'semestral' => $fechaBase->copy()->addMonthsNoOverflow(6 * $indice),
            'anual' => $fechaBase->copy()->addYears($indice),
            default => $fechaBase->copy()->addMonthsNoOverflow($indice),
        };
    }

    private function actualizarVencidos(int $socioId): void
    {
        PagoSocioDet::query()
            ->where('status', 'pendiente')
            ->whereDate('fecha_programada', '<', Carbon::today()->toDateString())
            ->whereHas('encabezado', function ($q) use ($socioId) {
                $q->where('id_socio', $socioId);
            })
            ->update([
                'status' => 'vencido',
                'updated_by' => optional(auth()->user())->name,
            ]);
    }

    private function guardarFotoSocio(Request $request, array $data, ?string $rutaActual = null): ?string
    {
        if (! $request->hasFile('foto_socio')) {
            return $rutaActual;
        }

        $file = $request->file('foto_socio');
        $folderKey = (string) ($data['numero_socio'] ?? '');
        if (!empty($data['numero_dependiente'])) {
            $folderKey .= '_' . (string) $data['numero_dependiente'];
        }
        $folderKey = trim($folderKey) !== '' ? trim($folderKey) : 'socio_' . now()->timestamp;

        $basePath = public_path('Sistema/Socios/' . $folderKey);
        if (!File::exists($basePath)) {
            File::makeDirectory($basePath, 0777, true, true);
        }

        if ($rutaActual && File::exists(public_path($rutaActual))) {
            File::delete(public_path($rutaActual));
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        $filename = 'foto_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.' . $ext;
        $file->move($basePath, $filename);

        return 'Sistema/Socios/' . $folderKey . '/' . $filename;
    }

    private function registrarMovimientoTesoreria(float $monto, string $tipoMovimiento, string $concepto, string $descripcion): void
    {
        if ($monto <= 0 || !Schema::hasTable('tblcuentas')) {
            return;
        }

        $usuario = optional(auth()->user())->name;
        $colDesc = Schema::hasColumn('tblcuentas', 'descripcion')
            ? 'descripcion'
            : (Schema::hasColumn('tblcuentas', 'nombre') ? 'nombre' : null);
        $colSaldo = Schema::hasColumn('tblcuentas', 'saldo_actual') ? 'saldo_actual' : null;
        if (!$colSaldo) {
            return;
        }

        $cuentaQuery = DB::table('tblcuentas')->select(['id', $colSaldo]);
        if ($colDesc) {
            $cuentaTesoreria = $cuentaQuery
                ->whereRaw('LOWER(' . $colDesc . ') like ?', ['%tesorer%'])
                ->first();
        } else {
            $cuentaTesoreria = null;
        }
        if (!$cuentaTesoreria) {
            $cuentaTesoreria = DB::table('tblcuentas')->select(['id', $colSaldo])->orderBy('id')->first();
        }
        if (!$cuentaTesoreria) {
            return;
        }

        $saldoActual = (float) ($cuentaTesoreria->{$colSaldo} ?? 0);
        $saldoNuevo = $tipoMovimiento === 'INGRESO' ? $saldoActual + $monto : $saldoActual - $monto;
        DB::table('tblcuentas')->where('id', $cuentaTesoreria->id)->update([$colSaldo => $saldoNuevo]);

        if (!Schema::hasTable('tblmovimientos_cuentas')) {
            return;
        }
        $insert = [];
        if (Schema::hasColumn('tblmovimientos_cuentas', 'id_cuenta')) $insert['id_cuenta'] = $cuentaTesoreria->id;
        if (Schema::hasColumn('tblmovimientos_cuentas', 'tipo_movimiento')) $insert['tipo_movimiento'] = $tipoMovimiento;
        if (Schema::hasColumn('tblmovimientos_cuentas', 'fecha_movimiento')) $insert['fecha_movimiento'] = Carbon::now()->toDateString();
        if (Schema::hasColumn('tblmovimientos_cuentas', 'fecha')) $insert['fecha'] = Carbon::now()->toDateString();
        if (Schema::hasColumn('tblmovimientos_cuentas', 'concepto')) $insert['concepto'] = $concepto;
        if (Schema::hasColumn('tblmovimientos_cuentas', 'descripcion')) $insert['descripcion'] = $descripcion;
        if (Schema::hasColumn('tblmovimientos_cuentas', 'monto')) $insert['monto'] = $monto;
        if (Schema::hasColumn('tblmovimientos_cuentas', 'saldo')) $insert['saldo'] = $saldoNuevo;
        if (Schema::hasColumn('tblmovimientos_cuentas', 'created_by')) $insert['created_by'] = $usuario;
        if (Schema::hasColumn('tblmovimientos_cuentas', 'updated_by')) $insert['updated_by'] = $usuario;
        if (Schema::hasColumn('tblmovimientos_cuentas', 'created_at')) $insert['created_at'] = now();
        if (Schema::hasColumn('tblmovimientos_cuentas', 'updated_at')) $insert['updated_at'] = now();

        if (!empty($insert)) {
            DB::table('tblmovimientos_cuentas')->insert($insert);
        }
    }

    private function resolverHojaDirectorioSocios(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): Worksheet
    {
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $titulo = mb_strtolower(trim((string) $sheet->getTitle()), 'UTF-8');
            if (str_contains($titulo, 'socios activos') || str_contains($titulo, 'directorio')) {
                return $sheet;
            }
        }

        return $spreadsheet->getActiveSheet();
    }

    private function detectarFilaEncabezadoSocios(Worksheet $sheet): int
    {
        $max = min(20, (int) $sheet->getHighestRow());
        for ($fila = 1; $fila <= $max; $fila++) {
            $textos = [];
            for ($col = 1; $col <= 15; $col++) {
                $textos[] = $this->normalizarHeader((string) $sheet->getCellByColumnAndRow($col, $fila)->getCalculatedValue());
            }
            $joined = implode(' ', array_filter($textos));
            if (
                (str_contains($joined, 'numero_socio') || str_contains($joined, 'socio'))
                && (str_contains($joined, 'nombre') || str_contains($joined, 'representante') || str_contains($joined, 'telefono') || str_contains($joined, 'correo'))
            ) {
                return $fila;
            }
        }

        return 0;
    }

    private function mapearColumnasDesdeEncabezado(Worksheet $sheet, int $filaEncabezado): array
    {
        $mapa = [];
        for ($col = 1; $col <= 20; $col++) {
            $header = $this->normalizarHeader((string) $sheet->getCellByColumnAndRow($col, $filaEncabezado)->getCalculatedValue());
            if ($header === '') {
                continue;
            }
            if (preg_match('/^(numero|no|#)?\s*socio$/', $header) || $header === 'numero_socio') {
                $mapa['numero_socio'] = $col;
            } elseif (str_contains($header, 'nombre_del_socio') || $header === 'empresa' || str_contains($header, 'razon')) {
                $mapa['empresa'] = $col;
            } elseif (str_contains($header, 'representante') || str_contains($header, 'contacto') || str_contains($header, 'legal')) {
                $mapa['contacto'] = $col;
            } elseif (str_contains($header, 'telefono') || $header === 'tel') {
                $mapa['telefono'] = $col;
            } elseif (str_contains($header, 'correo') || str_contains($header, 'email') || str_contains($header, 'mail')) {
                $mapa['correo'] = $col;
            } elseif (str_contains($header, 'cuota') || str_contains($header, 'periodicidad')) {
                $mapa['cuota'] = $col;
            } elseif (str_contains($header, 'facturacion') || str_contains($header, 'factura')) {
                $mapa['fecha_facturacion'] = $col;
            } elseif (str_contains($header, 'domicilio') || str_contains($header, 'direccion')) {
                $mapa['domicilio'] = $col;
            }
        }

        if (! isset($mapa['numero_socio'])) {
            return $this->mapearColumnasLayoutAlterno();
        }

        return $mapa;
    }

    /** Layout tipo Hoja2 o columnas fijas del directorio COPARMEX. */
    private function mapearColumnasLayoutAlterno(): array
    {
        return [
            'numero_socio' => 2,
            'cuota' => 3,
            'fecha_facturacion' => 4,
            'empresa' => 5,
            'contacto' => 6,
            'telefono' => 7,
            'correo' => 8,
            'domicilio' => 9,
        ];
    }

    private function extraerRegistroSocioDesdeFila(Worksheet $sheet, int $fila, array $mapa): ?array
    {
        $leer = function (string $key) use ($sheet, $fila, $mapa) {
            if (! isset($mapa[$key])) {
                return '';
            }
            return trim((string) $sheet->getCellByColumnAndRow($mapa[$key], $fila)->getCalculatedValue());
        };

        $numeroSocio = $leer('numero_socio');
        $empresa = $leer('empresa');
        $contacto = $leer('contacto');
        $telefono = $leer('telefono');
        $correo = $leer('correo');

        if ($numeroSocio === '' && $empresa === '' && $contacto === '' && $telefono === '' && $correo === '') {
            return null;
        }

        if ($numeroSocio === '' || mb_strtolower($numeroSocio, 'UTF-8') === '*') {
            return null;
        }

        return [
            'numero_socio' => $numeroSocio,
            'empresa' => $empresa,
            'contacto' => $contacto,
            'telefono' => $telefono,
            'correo' => $correo,
            'cuota' => $leer('cuota'),
            'fecha_facturacion' => $leer('fecha_facturacion'),
            'domicilio' => $leer('domicilio'),
        ];
    }

    private function normalizarHeader(string $valor): string
    {
        $v = mb_strtolower(trim($valor), 'UTF-8');
        $v = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $v);
        $v = preg_replace('/[^a-z0-9]+/', '_', $v) ?? '';
        return trim($v, '_');
    }

    private function parsearNombreContacto(string $contacto): array
    {
        $texto = trim(preg_replace('/\s+/', ' ', $contacto) ?? '');
        if ($texto === '') {
            return ['nombre' => '', 'segund_nom' => null, 'ap_paterno' => null, 'ap_materno' => null];
        }

        $texto = preg_replace('/^(c\.?\s*p\.?|lic\.?|ing\.?|sr\.?|sra\.?|dr\.?|dra\.?|arquitecto|arquitecta)\s+/iu', '', $texto) ?? $texto;
        $partes = preg_split('/\s+/', trim($texto)) ?: [];
        $partes = array_values(array_filter($partes, fn ($p) => $p !== ''));

        if (count($partes) === 1) {
            return ['nombre' => $partes[0], 'segund_nom' => null, 'ap_paterno' => null, 'ap_materno' => null];
        }
        if (count($partes) === 2) {
            return ['nombre' => $partes[0], 'segund_nom' => null, 'ap_paterno' => $partes[1], 'ap_materno' => null];
        }

        $apMaterno = array_pop($partes);
        $apPaterno = array_pop($partes);
        $nombre = array_shift($partes);
        $segundNom = count($partes) ? implode(' ', $partes) : null;

        return [
            'nombre' => $nombre ?: 'SIN NOMBRE',
            'segund_nom' => $segundNom,
            'ap_paterno' => $apPaterno,
            'ap_materno' => $apMaterno,
        ];
    }

    private function normalizarTelefono(?string $telefono): ?string
    {
        $tel = trim((string) $telefono);
        if ($tel === '') {
            return null;
        }

        return mb_substr(preg_replace('/\s+/', ' ', $tel) ?? $tel, 0, 40);
    }

    private function normalizarCorreo(?string $correo): ?string
    {
        $raw = trim((string) $correo);
        if ($raw === '') {
            return null;
        }
        $partes = preg_split('/[;,]/', $raw) ?: [];
        $primero = trim((string) ($partes[0] ?? ''));

        return $primero !== '' ? mb_substr($primero, 0, 250) : null;
    }

    private function valorTextoLargo(?string $valor): ?string
    {
        $v = trim((string) $valor);

        return $v !== '' ? $v : null;
    }

    private function valorTextoCorto(?string $valor, int $max): ?string
    {
        $v = trim((string) $valor);
        if ($v === '' || $v === '*') {
            return null;
        }

        return mb_substr($v, 0, $max);
    }

    private function normalizarModoTitularOAllegado(array &$data): void
    {
        $tipoTitular = TipoSocio::query()->whereRaw('LOWER(tipo_socio) = ?', ['titular'])->first();
        $tipoAllegado = TipoSocio::query()
            ->whereIn(DB::raw('LOWER(tipo_socio)'), ['afiliado', 'allegado', 'familiar', 'dependiente'])
            ->first();

        if (!empty($data['id_titular'])) {
            $titular = Socio::findOrFail((int) $data['id_titular']);
            $data['numero_socio'] = $titular->numero_socio;
            if (empty($data['numero_dependiente'])) {
                $maxDep = Socio::query()->where('numero_socio', $titular->numero_socio)->max('numero_dependiente');
                $data['numero_dependiente'] = ((int) $maxDep) + 1;
            }
            if ($tipoAllegado) {
                $data['id_tipo_socio'] = $tipoAllegado->id;
            }
            return;
        }

        $data['id_titular'] = null;
        $data['numero_dependiente'] = null;
        if ($tipoTitular) {
            $data['id_tipo_socio'] = $tipoTitular->id;
        }
    }
}
