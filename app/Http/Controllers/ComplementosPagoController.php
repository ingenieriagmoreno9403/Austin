<?php

namespace App\Http\Controllers;

use App\Models\ComplementoPago;
use App\Models\facturacionproductos;
use App\Traits\MenuTrait;
use App\Traits\DatosimpleTraits;
use App\Traits\SistemasTraits;
use App\Traits\GlobalTraits;
use App\Traits\FacturamaTraits;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ComplementosPagoController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use GlobalTraits;
    use FacturamaTraits;

    private const FORMAS_PAGO = [
        '01' => '01 - Efectivo',
        '02' => '02 - Cheque nominativo',
        '03' => '03 - Transferencia electrónica',
        '04' => '04 - Tarjeta de crédito',
        '28' => '28 - Tarjeta de débito',
        '99' => '99 - Por definir',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $permisoGestion = $this->forpermisos('gestion_complementos_pago');

            $facturasPpd = facturacionproductos::query()
                ->whereNotNull('Uuid')
                ->where('Uuid', '!=', '')
                ->where('cancelada', 'A')
                ->where('metodo_pago', 'PPD')
                ->where(function ($q) {
                    $q->whereNull('estado')
                        ->orWhere('estado', '!=', 'Pagado');
                })
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();

            // Excluir facturas sin saldo pendiente (ya cubiertas con complementos timbrados)
            if ($facturasPpd->isNotEmpty()) {
                $pagadoPorFactura = ComplementoPago::query()
                    ->whereIn('id_factura_original', $facturasPpd->pluck('id'))
                    ->where('estado', 'Timbrado')
                    ->selectRaw('id_factura_original, SUM(monto_pagado) as total_pagado')
                    ->groupBy('id_factura_original')
                    ->pluck('total_pagado', 'id_factura_original');

                $facturasPpd = $facturasPpd
                    ->filter(function ($factura) use ($pagadoPorFactura) {
                        $pagado = (float) ($pagadoPorFactura[$factura->id] ?? 0);
                        $saldo = round(((float) $factura->total) - $pagado, 2);

                        return $saldo > 0.01;
                    })
                    ->values();
            }

            $complementos = ComplementoPago::orderByDesc('created_at')->get();

            $facturasRelacionadas = facturacionproductos::query()
                ->whereIn('id', $complementos->pluck('id_factura_original')->filter()->unique())
                ->get(['id', 'facturama_id', 'Uuid', 'folio'])
                ->keyBy('id');

            $formasPago = self::FORMAS_PAGO;
            $emisor = $this->obtenerDatosEmisorFiscal();

            return view('Tesoreria.ComplementosPago.index', compact(
                'varpantallas',
                'varsubmenus',
                'permisoGestion',
                'facturasPpd',
                'complementos',
                'facturasRelacionadas',
                'formasPago',
                'emisor'
            ));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with('warningBD', 'no guardado correctamente');
        }
    }

    public function obtenerFactura(int $id)
    {
        $factura = facturacionproductos::find($id);

        if (!$factura) {
            return response()->json(['success' => false, 'message' => 'Factura no encontrada'], 404);
        }

        if (empty($factura->Uuid)) {
            return response()->json([
                'success' => false,
                'message' => 'La factura no está timbrada (sin UUID)',
            ], 422);
        }

        if ($factura->cancelada === 'C') {
            return response()->json([
                'success' => false,
                'message' => 'La factura está cancelada',
            ], 422);
        }

        if (strtoupper((string) $factura->metodo_pago) !== 'PPD') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se permiten facturas con método de pago PPD',
            ], 422);
        }

        $complementosPrevios = ComplementoPago::where('id_factura_original', $id)
            ->where('estado', 'Timbrado')
            ->orderBy('numero_parcialidad')
            ->get();

        $totalPagado = (float) $complementosPrevios->sum('monto_pagado');
        $saldoInsoluto = round(((float) $factura->total) - $totalPagado, 2);
        $siguienteParcialidad = $complementosPrevios->count() + 1;

        $cfdiOriginal = $this->obtenerCfdiFacturama($factura->facturama_id);
        $receptor = $this->obtenerDatosReceptorFiscal($factura, $cfdiOriginal);

        return response()->json([
            'success' => true,
            'factura' => [
                'id' => $factura->id,
                'folio' => $factura->folio,
                'uuid' => $factura->Uuid,
                'fecha' => $factura->date,
                'receptor_rfc' => $factura->reciver_rfc,
                'receptor_nombre' => $factura->reciver_nombre,
                'total' => (float) $factura->total,
                'metodo_pago' => $factura->metodo_pago,
                'forma_pago' => $factura->forma_pago ?? '99',
                'facturama_id' => $factura->facturama_id,
            ],
            'receptor' => $receptor,
            'total_pagado' => $totalPagado,
            'saldo_insoluto' => max(0, $saldoInsoluto),
            'siguiente_parcialidad' => $siguienteParcialidad,
            'complementos_previos' => $complementosPrevios->map(fn ($c) => [
                'parcialidad' => $c->numero_parcialidad,
                'monto' => (float) $c->monto_pagado,
                'saldo_insoluto' => (float) $c->saldo_insoluto,
                'fecha' => $c->fecha_pago?->format('d/m/Y'),
            ]),
            'cfdi_original' => $cfdiOriginal,
        ]);
    }

    public function emitir(Request $request)
    {
        if ($this->forpermisos('gestion_complementos_pago') !== 'gestion_complementos_pago') {
            return response()->json(['success' => false, 'message' => 'Sin permisos para emitir complementos de pago'], 403);
        }

        $request->validate([
            'factura_id' => 'required|integer|exists:tblfacturacionproductos,id',
            'fecha_pago' => 'required|date',
            'forma_pago' => 'required|in:' . implode(',', array_keys(self::FORMAS_PAGO)),
            'monto_pagado' => 'required|numeric|min:0.01',
        ]);

        $factura = facturacionproductos::find($request->factura_id);

        if (!$factura || empty($factura->Uuid) || $factura->cancelada === 'C') {
            return response()->json([
                'success' => false,
                'message' => 'La factura original no es válida',
            ], 422);
        }

        if (strtoupper((string) $factura->metodo_pago) !== 'PPD') {
            return response()->json([
                'success' => false,
                'message' => 'El complemento de pago solo aplica a facturas PPD',
            ], 422);
        }

        $complementosPrevios = ComplementoPago::where('id_factura_original', $factura->id)
            ->where('estado', 'Timbrado')
            ->get();

        $totalPagado = (float) $complementosPrevios->sum('monto_pagado');
        $saldoAnterior = round(((float) $factura->total) - $totalPagado, 2);
        $montoPagado = round((float) $request->monto_pagado, 2);
        $numeroParcialidad = $complementosPrevios->count() + 1;

        if ($saldoAnterior <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'La factura ya está totalmente pagada',
            ], 422);
        }

        if ($montoPagado > $saldoAnterior + 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'El monto excede el saldo insoluto (\$' . number_format($saldoAnterior, 2) . ')',
            ], 422);
        }

        $saldoInsoluto = round($saldoAnterior - $montoPagado, 2);
        $fechaPago = Carbon::parse($request->fecha_pago);

        $resultado = $this->timbrarComplementoPago(
            $factura,
            $request->forma_pago,
            $montoPagado,
            $saldoAnterior,
            $saldoInsoluto,
            $numeroParcialidad,
            $fechaPago
        );

        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'message' => $resultado['message'],
            ], 422);
        }

        $datos = $resultado['data'];

        $complemento = ComplementoPago::create([
            'id_factura_original' => $factura->id,
            'factura_uuid' => $factura->Uuid,
            'folio_factura' => $factura->folio,
            'fecha_pago' => $fechaPago,
            'forma_pago' => $request->forma_pago,
            'numero_parcialidad' => $numeroParcialidad,
            'saldo_anterior' => $saldoAnterior,
            'monto_pagado' => $montoPagado,
            'saldo_insoluto' => $saldoInsoluto,
            'receptor_rfc' => $factura->reciver_rfc,
            'receptor_nombre' => $factura->reciver_nombre,
            'estado' => 'Timbrado',
            'facturama_id' => $datos['Id'] ?? $datos['id'] ?? null,
            'folio_complemento' => $datos['Folio'] ?? null,
            'uuid' => $datos['Complement']['TaxStamp']['Uuid'] ?? null,
            'respuesta_api' => json_encode($datos),
            'created_by' => auth()->user()->name ?? 'system',
        ]);

        if ($saldoInsoluto <= 0.01) {
            $factura->update(['estado' => 'Pagado']);
        } elseif ($factura->estado !== 'Cobrando') {
            $factura->update(['estado' => 'Cobrando']);
        }

        try {
            app(\App\Services\FacturacionFinanzasService::class)->procesarPostComplemento(
                $factura->fresh(),
                $montoPagado,
                $request->forma_pago,
                $fechaPago
            );
        } catch (\Throwable $e) {
            Log::error('Error al registrar complemento en Finanzas: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Complemento de pago (REP) timbrado correctamente',
            'complemento' => $complemento->fresh(),
            'limite_emision' => $this->calcularLimiteEmision($fechaPago),
        ]);
    }

    public function verFactura(int $id)
    {
        $complemento = ComplementoPago::findOrFail($id);

        if (empty($complemento->facturama_id)) {
            return back()->with('warning', 'El complemento no tiene identificador de Facturama');
        }

        try {
            $base64 = $this->obtenerPdfBase64Facturama($complemento->facturama_id);
            $filename = 'ComplementoPago_' . ($complemento->folio_complemento ?? $complemento->id) . '.pdf';

            return $this->respuestaPdfDesdeBase64($base64, $filename, true);
        } catch (\Exception $e) {
            Log::error('Error al ver PDF complemento de pago', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('warning', 'No se pudo abrir el PDF del complemento: ' . $e->getMessage());
        }
    }

    public function descargarPdf(int $id)
    {
        $complemento = ComplementoPago::findOrFail($id);

        if (empty($complemento->facturama_id)) {
            return back()->with('warning', 'El complemento no tiene identificador de Facturama');
        }

        try {
            $base64 = $this->obtenerPdfBase64Facturama($complemento->facturama_id);
            $filename = 'ComplementoPago_' . ($complemento->folio_complemento ?? $complemento->id) . '.pdf';

            return $this->respuestaPdfDesdeBase64($base64, $filename, false);
        } catch (\Exception $e) {
            Log::error('Error al descargar PDF complemento de pago', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('warning', 'No se pudo descargar el PDF del complemento: ' . $e->getMessage());
        }
    }

    private function timbrarComplementoPago(
        object $factura,
        string $formaPago,
        float $montoPagado,
        float $saldoAnterior,
        float $saldoInsoluto,
        int $numeroParcialidad,
        Carbon $fechaPago
    ): array {
        $emisor = $this->obtenerDatosEmisorFiscal();
        $receptor = $this->obtenerDatosReceptor($factura);

        $payload = [
            'CfdiType' => 'P',
            'NameId' => '14',
            'ExpeditionPlace' => (string) $emisor['lugar_expedicion'],
            'Issuer' => [
                'FiscalRegime' => $emisor['regimen_fiscal'],
                'Rfc' => $emisor['rfc'],
                'Name' => $emisor['nombre'],
            ],
            'Receiver' => [
                'Rfc' => $receptor['rfc'],
                'CfdiUse' => 'CP01',
                'Name' => $receptor['nombre'],
                'FiscalRegime' => $receptor['regimen_fiscal'],
                'TaxZipCode' => $receptor['codigo_postal'],
            ],
            'Complemento' => [
                'Payments' => [
                    [
                        'Date' => $fechaPago->format('Y-m-d\TH:i:s'),
                        'PaymentForm' => $formaPago,
                        'Amount' => number_format($montoPagado, 2, '.', ''),
                        'RelatedDocuments' => [
                            [
                                'TaxObject' => '01',
                                'Uuid' => $factura->Uuid,
                                'Folio' => $factura->folio,
                                'PaymentMethod' => 'PPD',
                                'PartialityNumber' => (string) $numeroParcialidad,
                                'PreviousBalanceAmount' => number_format($saldoAnterior, 2, '.', ''),
                                'AmountPaid' => number_format($montoPagado, 2, '.', ''),
                                'ImpSaldoInsoluto' => number_format($saldoInsoluto, 2, '.', ''),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $client = $this->crearClienteFacturama();
            $response = $client->post('/3/cfdis', [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $payload,
            ]);

            return ['success' => true, 'data' => json_decode($response->getBody()->getContents(), true)];
        } catch (RequestException $e) {
            $errorDetalle = $this->extraerErrorFacturama($e);

            Log::error('Error al timbrar complemento de pago', [
                'factura_id' => $factura->id,
                'payload' => $payload,
                'error' => $errorDetalle,
            ]);

            return [
                'success' => false,
                'message' => $errorDetalle,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error inesperado al timbrar: ' . $e->getMessage(),
            ];
        }
    }

    private function calcularLimiteEmision(Carbon $fechaPago): string
    {
        return $fechaPago->copy()->addMonth()->day(5)->format('d/m/Y');
    }

    private function obtenerDatosReceptor(object $factura): array
    {
        $cfdi = $this->obtenerCfdiFacturama($factura->facturama_id);
        $datos = $this->obtenerDatosReceptorFiscal($factura, $cfdi);

        return [
            'rfc' => $datos['rfc'],
            'nombre' => $datos['nombre'],
            'regimen_fiscal' => $datos['regimen_fiscal'],
            'codigo_postal' => $datos['codigo_postal'],
        ];
    }

    private function obtenerCfdiFacturama(?string $facturamaId): ?array
    {
        if (empty($facturamaId)) {
            return null;
        }

        try {
            $client = $this->crearClienteFacturama();
            $response = $client->get("/3/cfdis/{$facturamaId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function crearClienteFacturama(): Client
    {
        return new Client([
            'base_uri' => env('FACTURAMA_BASE_URI', 'https://apisandbox.facturama.mx'),
            'timeout' => 60.0,
            'auth' => [env('USER_FAC'), env('PWD')],
            'verify' => false,
        ]);
    }
}
