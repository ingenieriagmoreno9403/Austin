<?php

namespace App\Services;

use App\Models\DeudaCobrar;
use App\Models\Ingreso;
use App\Models\facturacionproductos;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacturacionFinanzasService
{
    public const CUENTA_DEFAULT_ID = 3;

    public function resolverEstadoCobranzaInicial(?string $metodoPago): string
    {
        return strtoupper(trim($metodoPago ?? 'PUE')) === 'PPD' ? 'Pendiente' : 'Pagado';
    }

    public function procesarPostTimbrado(facturacionproductos $factura): void
    {
        if (($factura->cancelada ?? 'A') === 'C' || empty($factura->Uuid)) {
            return;
        }

        $metodo = strtoupper(trim($factura->metodo_pago ?? 'PUE'));

        if ($metodo === 'PPD') {
            $this->crearDeudaDesdeFactura($factura);
            return;
        }

        $this->registrarIngresoDesdeFactura(
            $factura,
            (float) $factura->total,
            'Factura timbrada PUE folio ' . ($factura->folio ?? $factura->id),
            $factura->forma_pago,
            $factura->date,
            true
        );
    }

    public function procesarPostComplemento(
        facturacionproductos $factura,
        float $montoPagado,
        ?string $formaPago = null,
        $fechaPago = null
    ): void {
        if ($montoPagado <= 0) {
            return;
        }

        $this->registrarIngresoDesdeFactura(
            $factura,
            $montoPagado,
            'Complemento de pago folio ' . ($factura->folio ?? $factura->id),
            $formaPago,
            $fechaPago,
            false
        );

        $this->aplicarPagoADeuda($factura, $montoPagado, $fechaPago);
    }

    protected function crearDeudaDesdeFactura(facturacionproductos $factura): void
    {
        $yaExiste = DeudaCobrar::where('id_factura', $factura->id)
            ->whereNotIn('estado', ['cobrada', 'COBRADA'])
            ->exists();

        if ($yaExiste) {
            return;
        }

        $fechaBase = $factura->date ? Carbon::parse($factura->date) : now();

        DeudaCobrar::create([
            'deudor' => $factura->reciver_nombre ?? 'Cliente sin nombre',
            'monto' => (float) $factura->total,
            'fecha_vencimiento' => $fechaBase->copy()->addDays(30)->format('Y-m-d'),
            'estado' => 'pendiente',
            'descripcion' => 'CFDI PPD folio ' . ($factura->folio ?? $factura->id)
                . ($factura->Uuid ? ' · UUID ' . $factura->Uuid : ''),
            'id_venta' => $factura->id_serv_enc,
            'id_factura' => $factura->id,
            'monto_cobrado' => 0,
            'created_by' => auth()->id(),
        ]);
    }

    protected function registrarIngresoDesdeFactura(
        facturacionproductos $factura,
        float $monto,
        string $concepto,
        ?string $formaPago = null,
        $fecha = null,
        bool $evitarDuplicadoPue = false
    ): void {
        if ($monto <= 0) {
            return;
        }

        if ($evitarDuplicadoPue && Ingreso::where('id_factura', $factura->id)->exists()) {
            return;
        }

        $fechaIngreso = $fecha ? Carbon::parse($fecha)->format('Y-m-d') : now()->format('Y-m-d');

        $ingreso = Ingreso::create([
            'concepto' => $concepto,
            'monto' => round($monto, 2),
            'fecha' => $fechaIngreso,
            'categoria' => 'Facturación CFDI',
            'metodo_pago' => $this->mapFormaPagoLabel($formaPago ?? $factura->forma_pago),
            'descripcion' => 'Cliente: ' . ($factura->reciver_nombre ?? '-')
                . ' · RFC: ' . ($factura->reciver_rfc ?? '-'),
            'id_venta' => $factura->id_serv_enc,
            'id_factura' => $factura->id,
            'cuenta_id' => self::CUENTA_DEFAULT_ID,
            'created_by' => auth()->id(),
        ]);

        $this->actualizarSaldoCuenta($ingreso, 'ingreso', $factura->id_serv_enc ?? 0);
    }

    protected function aplicarPagoADeuda(facturacionproductos $factura, float $monto, $fechaPago = null): void
    {
        $deuda = DeudaCobrar::where('id_factura', $factura->id)
            ->whereNotIn('estado', ['cobrada', 'COBRADA'])
            ->first();

        if (!$deuda) {
            return;
        }

        $nuevoCobrado = round((float) ($deuda->monto_cobrado ?? 0) + $monto, 2);
        $updates = ['monto_cobrado' => $nuevoCobrado];

        if ($nuevoCobrado >= round((float) $deuda->monto - 0.01, 2)) {
            $updates['estado'] = 'cobrada';
            $updates['fecha_cobro'] = $fechaPago
                ? Carbon::parse($fechaPago)->format('Y-m-d')
                : now()->format('Y-m-d');
        }

        $deuda->update($updates);
    }

    protected function actualizarSaldoCuenta(Ingreso $ingreso, string $tipo, $servicioId = 0): void
    {
        try {
            $cuentaId = $ingreso->cuenta_id ?: self::CUENTA_DEFAULT_ID;
            $cuenta = DB::table('tblcuentas')->where('id', $cuentaId)->first();

            if (!$cuenta) {
                Log::error('FacturacionFinanzasService: cuenta no encontrada ID ' . $cuentaId);
                return;
            }

            $saldoAnterior = (float) $cuenta->saldo_actual;
            $saldoNuevo = $tipo === 'ingreso'
                ? $saldoAnterior + (float) $ingreso->monto
                : $saldoAnterior - (float) $ingreso->monto;

            DB::table('tblcuentas')
                ->where('id', $cuentaId)
                ->update(['saldo_actual' => $saldoNuevo]);

            DB::table('tblcuentas_bitacora')->insert([
                'id_cuenta' => (string) $cuentaId,
                'id_ent_salida' => $ingreso->id,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
                'fecha' => now(),
                'usuario' => auth()->user()->name ?? (string) (auth()->id() ?? 'system'),
                'servicio_id' => $servicioId ?? 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('FacturacionFinanzasService: error al actualizar saldo: ' . $e->getMessage());
        }
    }

    protected function mapFormaPagoLabel(?string $formaPago): string
    {
        $codigo = str_pad(trim((string) $formaPago), 2, '0', STR_PAD_LEFT);

        $map = [
            '01' => 'Efectivo',
            '02' => 'Cheque nominativo',
            '03' => 'Transferencia electrónica',
            '04' => 'Tarjeta de crédito',
            '28' => 'Tarjeta de débito',
            '99' => 'Por definir',
        ];

        return $map[$codigo] ?? 'Transferencia electrónica';
    }
}
