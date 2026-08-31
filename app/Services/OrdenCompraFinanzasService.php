<?php

namespace App\Services;

use App\Models\DeudaPagar;
use App\Models\Egreso;
use App\Models\OrdenCompra;
use App\Models\PagoDeudaPagar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OrdenCompraFinanzasService
{
    public const CUENTA_DEFAULT_ID = 3;

    /**
     * @return array{subtotal: float, iva: float, total: float}
     */
    public function calcularTotales(OrdenCompra $orden): array
    {
        $orden->loadMissing('detalles');

        $subtotal = round((float) $orden->detalles->sum(function ($d) {
            return ((float) $d->cantidad) * ((float) $d->costo);
        }), 2);

        $iva = ((int) ($orden->iva_aplicado ?? 1) === 1)
            ? round($subtotal * 0.16, 2)
            : 0.0;

        return [
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => round($subtotal + $iva, 2),
        ];
    }

    public function resolverFechaVencimiento(OrdenCompra $orden): string
    {
        $orden->loadMissing('proveedor');

        if (! empty($orden->fecha_tentativa_pago)) {
            return Carbon::parse($orden->fecha_tentativa_pago)->format('Y-m-d');
        }

        $base = $orden->fecha_limite
            ? Carbon::parse($orden->fecha_limite)
            : now();

        $dias = (int) (optional($orden->proveedor)->dias_credito ?? 0);

        return $base->copy()->addDays(max(0, $dias))->format('Y-m-d');
    }

    public function deudaActivaDeOrden(OrdenCompra $orden): ?DeudaPagar
    {
        return DeudaPagar::query()
            ->where('orden_compra_id', $orden->id)
            ->whereNotIn('estado', ['anulada'])
            ->latest('id')
            ->first();
    }

    public function crearDeudaDesdeOrden(OrdenCompra $orden): ?DeudaPagar
    {
        $existente = $this->deudaActivaDeOrden($orden);
        if ($existente) {
            return $existente;
        }

        $totales = $this->calcularTotales($orden);
        if ($totales['total'] <= 0) {
            Log::warning('OC sin total para deuda por pagar', ['orden_id' => $orden->id]);
            return null;
        }

        $orden->loadMissing('proveedor');
        $acreedor = optional($orden->proveedor)->nombre ?: ('Proveedor OC ' . ($orden->folio ?: $orden->id));

        return DeudaPagar::create([
            'orden_compra_id' => $orden->id,
            'proveedor_id' => $orden->proveedor_id,
            'acreedor' => $acreedor,
            'monto' => $totales['total'],
            'monto_pagado' => 0,
            'tipo_moneda' => $orden->tipo_moneda ?: 'MXN',
            'fecha_vencimiento' => $this->resolverFechaVencimiento($orden),
            'estado' => 'pendiente',
            'descripcion' => 'OC ' . ($orden->folio ?: ('#' . $orden->id))
                . ($orden->nombre ? ' · ' . $orden->nombre : ''),
            'created_by' => auth()->id(),
        ]);
    }

    public function registrarAbono(DeudaPagar $deuda, array $datos): PagoDeudaPagar
    {
        $monto = round((float) ($datos['monto'] ?? 0), 2);
        if ($monto <= 0) {
            throw new InvalidArgumentException('El monto del abono debe ser mayor a cero.');
        }

        if (in_array($deuda->estado, ['pagada', 'anulada'], true)) {
            throw new InvalidArgumentException('Esta deuda ya no admite abonos.');
        }

        $pagado = round((float) ($deuda->monto_pagado ?? 0), 2);
        $total = round((float) $deuda->monto, 2);
        $saldo = round($total - $pagado, 2);

        if ($monto - $saldo > 0.009) {
            throw new InvalidArgumentException('El abono ($' . number_format($monto, 2) . ') supera el saldo pendiente ($' . number_format($saldo, 2) . ').');
        }

        return DB::transaction(function () use ($deuda, $datos, $monto, $pagado, $total) {
            $cuentaId = ! empty($datos['cuenta_id']) ? (int) $datos['cuenta_id'] : null;
            $fecha = ! empty($datos['fecha'])
                ? Carbon::parse($datos['fecha'])->format('Y-m-d')
                : now()->format('Y-m-d');
            $metodo = trim((string) ($datos['metodo_pago'] ?? 'Transferencia electrónica'));
            $referencia = trim((string) ($datos['referencia'] ?? ''));
            $notas = trim((string) ($datos['notas'] ?? ''));

            $egresoId = null;
            if ($cuentaId) {
                $egreso = Egreso::create([
                    'concepto' => 'Abono OC ' . ($deuda->descripcion ?: ('#' . $deuda->id)),
                    'monto' => $monto,
                    'fecha' => $fecha,
                    'categoria' => 'Proveedores',
                    'metodo_pago' => $metodo,
                    'descripcion' => trim('Pago a ' . $deuda->acreedor
                        . ($referencia !== '' ? ' · Ref. ' . $referencia : '')
                        . ($notas !== '' ? ' · ' . $notas : '')),
                    'cuenta_id' => $cuentaId,
                    'created_by' => auth()->id(),
                ]);
                $this->actualizarSaldoCuenta($egreso, 'egreso');
                $egresoId = $egreso->id;
            }

            $pago = PagoDeudaPagar::create([
                'deuda_pagar_id' => $deuda->id,
                'orden_compra_id' => $deuda->orden_compra_id,
                'monto' => $monto,
                'fecha' => $fecha,
                'metodo_pago' => $metodo,
                'referencia' => $referencia !== '' ? $referencia : null,
                'cuenta_id' => $cuentaId,
                'egreso_id' => $egresoId,
                'notas' => $notas !== '' ? $notas : null,
                'created_by' => auth()->id(),
            ]);

            $nuevoPagado = round($pagado + $monto, 2);
            $updates = [
                'monto_pagado' => $nuevoPagado,
                'updated_by' => auth()->id(),
            ];

            if ($nuevoPagado >= round($total - 0.01, 2)) {
                $updates['estado'] = 'pagada';
                $updates['fecha_pago'] = $fecha;
            } else {
                $updates['estado'] = 'parcial';
            }

            $deuda->update($updates);

            return $pago;
        });
    }

    public function resumenDeuda(?DeudaPagar $deuda): array
    {
        if (! $deuda) {
            return [
                'existe' => false,
                'monto' => 0,
                'pagado' => 0,
                'saldo' => 0,
                'estado' => null,
                'fecha_vencimiento' => null,
                'dias' => null,
            ];
        }

        $monto = round((float) $deuda->monto, 2);
        $pagado = round((float) ($deuda->monto_pagado ?? 0), 2);
        $saldo = round(max(0, $monto - $pagado), 2);
        $vence = $deuda->fecha_vencimiento ? Carbon::parse($deuda->fecha_vencimiento)->startOfDay() : null;
        $dias = $vence ? (int) now()->startOfDay()->diffInDays($vence, false) : null;

        return [
            'existe' => true,
            'id' => $deuda->id,
            'monto' => $monto,
            'pagado' => $pagado,
            'saldo' => $saldo,
            'estado' => $deuda->estado,
            'fecha_vencimiento' => $vence ? $vence->format('Y-m-d') : null,
            'dias' => $dias,
            'moneda' => $deuda->tipo_moneda ?: 'MXN',
        ];
    }

    protected function actualizarSaldoCuenta(Egreso $egreso, string $tipo): void
    {
        try {
            $cuentaId = $egreso->cuenta_id ?: self::CUENTA_DEFAULT_ID;
            $cuenta = DB::table('tblcuentas')->where('id', $cuentaId)->first();
            if (! $cuenta) {
                Log::error('OrdenCompraFinanzasService: cuenta no encontrada ID ' . $cuentaId);
                return;
            }

            $saldoAnterior = (float) $cuenta->saldo_actual;
            $saldoNuevo = $tipo === 'ingreso'
                ? $saldoAnterior + (float) $egreso->monto
                : $saldoAnterior - (float) $egreso->monto;

            DB::table('tblcuentas')->where('id', $cuentaId)->update(['saldo_actual' => $saldoNuevo]);

            DB::table('tblcuentas_bitacora')->insert([
                'id_cuenta' => (string) $cuentaId,
                'id_ent_salida' => $egreso->id,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
                'fecha' => now(),
                'usuario' => auth()->user()->name ?? (string) (auth()->id() ?? 'system'),
                'servicio_id' => 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('OrdenCompraFinanzasService: error al actualizar saldo: ' . $e->getMessage());
        }
    }
}
