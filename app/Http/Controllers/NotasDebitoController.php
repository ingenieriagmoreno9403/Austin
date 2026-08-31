<?php

namespace App\Http\Controllers;

use App\Models\NotaCredito;
use App\Models\NotaDebito;
use App\Models\facturacionproductos;
use App\Traits\MenuTrait;
use App\Traits\DatosimpleTraits;
use App\Traits\SistemasTraits;
use App\Traits\GlobalTraits;
use App\Traits\FacturamaTraits;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotasDebitoController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use GlobalTraits;
    use FacturamaTraits;

    private const MOTIVOS = [
        'error_favor_vendedor' => [
            'label' => 'Error de Facturación a Favor del Vendedor',
            'descripcion' => 'Cobro adicional por error en la factura original a favor del vendedor',
            'tipo_relacion' => '02',
        ],
        'intereses_moratorios' => [
            'label' => 'Intereses o Recargos por Mora',
            'descripcion' => 'Intereses moratorios o recargos por retraso en el pago',
            'tipo_relacion' => '02',
        ],
        'gastos_logisticos' => [
            'label' => 'Gastos de Envío o Logística',
            'descripcion' => 'Flete o costos logísticos no incluidos en la factura original',
            'tipo_relacion' => '02',
        ],
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
            $permisoGestion = $this->forpermisos('gestion_notas_debito');

            $facturasTimbradas = facturacionproductos::query()
                ->whereNotNull('Uuid')
                ->where('Uuid', '!=', '')
                ->where('cancelada', 'A')
                ->where('metodo_pago', 'PUE')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();

            $notasDebito = NotaDebito::orderByDesc('created_at')->get();
            $motivos = self::MOTIVOS;
            $emisor = $this->obtenerDatosEmisorFiscal();

            return view('Tesoreria.NotasDebito.index', compact(
                'varpantallas',
                'varsubmenus',
                'permisoGestion',
                'facturasTimbradas',
                'notasDebito',
                'motivos',
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
                'message' => 'La factura seleccionada no está timbrada (sin UUID)',
            ], 422);
        }

        if ($factura->cancelada === 'C') {
            return response()->json([
                'success' => false,
                'message' => 'La factura seleccionada está cancelada',
            ], 422);
        }

        if (strtoupper((string) $factura->metodo_pago) !== 'PUE') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se permiten facturas con método de pago PUE',
            ], 422);
        }

        $debitosAplicados = (float) NotaDebito::where('id_factura_original', $id)
            ->whereIn('estado', ['Timbrada', 'Registrada'])
            ->sum('total');

        $creditosAplicados = (float) NotaCredito::where('id_factura_original', $id)
            ->where('estado', 'Timbrada')
            ->sum('total');

        $saldoActual = round(((float) $factura->total) + $debitosAplicados - $creditosAplicados, 2);

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
                'subtotal' => (float) $factura->subtotal,
                'total' => (float) $factura->total,
                'iva' => round(((float) $factura->total) - ((float) $factura->subtotal), 2),
                'metodo_pago' => $factura->metodo_pago,
                'facturama_id' => $factura->facturama_id,
            ],
            'receptor' => $receptor,
            'notas_debito_aplicadas' => $debitosAplicados,
            'notas_credito_aplicadas' => $creditosAplicados,
            'saldo_actual' => $saldoActual,
            'cfdi_original' => $cfdiOriginal,
        ]);
    }

    public function emitir(Request $request)
    {
        if ($this->forpermisos('gestion_notas_debito') !== 'gestion_notas_debito') {
            return response()->json(['success' => false, 'message' => 'Sin permisos para emitir notas de débito'], 403);
        }

        $request->validate([
            'factura_id' => 'required|integer|exists:tblfacturacionproductos,id',
            'motivo' => 'required|in:' . implode(',', array_keys(self::MOTIVOS)),
            'tipo_documento' => 'required|in:comercial,fiscal',
            'monto' => 'required|numeric|min:0.01',
            'descripcion_adicional' => 'nullable|string|max:500',
        ]);

        $factura = facturacionproductos::find($request->factura_id);

        if (!$factura || empty($factura->Uuid) || $factura->cancelada === 'C') {
            return response()->json([
                'success' => false,
                'message' => 'La factura original no es válida para generar nota de débito',
            ], 422);
        }

        if (strtoupper((string) $factura->metodo_pago) !== 'PUE') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden emitir notas de débito sobre facturas con método de pago PUE',
            ], 422);
        }

        $motivoConfig = self::MOTIVOS[$request->motivo];
        $montoSolicitado = round((float) $request->monto, 2);
        $subtotal = round($montoSolicitado / 1.16, 2);
        $iva = round($montoSolicitado - $subtotal, 2);
        $total = $montoSolicitado;

        $descripcionMotivo = $motivoConfig['descripcion'];
        if ($request->filled('descripcion_adicional')) {
            $descripcionMotivo .= ' - ' . trim($request->descripcion_adicional);
        }
        $descripcionMotivo .= ' - Factura relacionada: ' . $factura->folio . ' (UUID: ' . $factura->Uuid . ')';

        $nota = NotaDebito::create([
            'id_factura_original' => $factura->id,
            'factura_uuid' => $factura->Uuid,
            'folio_factura' => $factura->folio,
            'tipo_documento' => $request->tipo_documento,
            'motivo' => $request->motivo,
            'motivo_descripcion' => $descripcionMotivo,
            'tipo_relacion' => $request->tipo_documento === 'fiscal' ? $motivoConfig['tipo_relacion'] : null,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
            'receptor_rfc' => $factura->reciver_rfc,
            'receptor_nombre' => $factura->reciver_nombre,
            'estado' => 'Pendiente',
            'created_by' => auth()->user()->name ?? 'system',
        ]);

        if ($request->tipo_documento === 'comercial') {
            $folioComercial = 'NDC-' . str_pad((string) $nota->id, 6, '0', STR_PAD_LEFT);
            $nota->update([
                'estado' => 'Registrada',
                'folio_nota' => $folioComercial,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nota de débito comercial registrada correctamente (sin validez fiscal)',
                'nota' => $nota->fresh(),
            ]);
        }

        $resultado = $this->timbrarNotaDebito($factura, $motivoConfig, $descripcionMotivo, $subtotal, $iva, $total);

        if (!$resultado['success']) {
            $nota->update([
                'estado' => 'Error',
                'respuesta_api' => json_encode($resultado),
            ]);

            return response()->json([
                'success' => false,
                'message' => $resultado['message'],
            ], 422);
        }

        $datos = $resultado['data'];

        $nota->update([
            'estado' => 'Timbrada',
            'facturama_id' => $datos['Id'] ?? $datos['id'] ?? null,
            'folio_nota' => $datos['Folio'] ?? null,
            'uuid' => $datos['Complement']['TaxStamp']['Uuid'] ?? null,
            'respuesta_api' => json_encode($datos),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Nota de débito fiscal timbrada correctamente (CFDI Ingreso)',
            'nota' => $nota->fresh(),
        ]);
    }

    public function verFactura(int $id)
    {
        $nota = NotaDebito::findOrFail($id);

        if ($nota->tipo_documento !== 'fiscal' || empty($nota->facturama_id)) {
            return back()->with('warning', 'Solo las notas de débito fiscales timbradas tienen CFDI para visualizar');
        }

        try {
            $base64 = $this->obtenerPdfBase64Facturama($nota->facturama_id);
            $filename = 'NotaDebito_' . ($nota->folio_nota ?? $nota->id) . '.pdf';

            return $this->respuestaPdfDesdeBase64($base64, $filename, true);
        } catch (\Exception $e) {
            Log::error('Error al ver PDF nota de débito', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('warning', 'No se pudo abrir el PDF de la nota de débito: ' . $e->getMessage());
        }
    }

    public function descargarPdf(int $id)
    {
        $nota = NotaDebito::findOrFail($id);

        if ($nota->tipo_documento !== 'fiscal' || empty($nota->facturama_id)) {
            return back()->with('warning', 'Solo las notas de débito fiscales timbradas tienen PDF');
        }

        try {
            $base64 = $this->obtenerPdfBase64Facturama($nota->facturama_id);
            $filename = 'NotaDebito_' . ($nota->folio_nota ?? $nota->id) . '.pdf';

            return $this->respuestaPdfDesdeBase64($base64, $filename, false);
        } catch (\Exception $e) {
            Log::error('Error al descargar PDF nota de débito', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('warning', 'No se pudo descargar el PDF de la nota de débito: ' . $e->getMessage());
        }
    }

    private function timbrarNotaDebito(
        object $factura,
        array $motivoConfig,
        string $descripcion,
        float $subtotal,
        float $iva,
        float $total
    ): array {
        $emisor = $this->obtenerDatosEmisorFiscal();
        $receptor = $this->obtenerDatosReceptor($factura);

        $payload = [
            'CfdiType' => 'I',
            'PaymentForm' => '01',
            'PaymentMethod' => 'PUE',
            'ExpeditionPlace' => (string) $emisor['lugar_expedicion'],
            'Date' => now()->format('Y-m-d H:i:s'),
            'Issuer' => [
                'FiscalRegime' => $emisor['regimen_fiscal'],
                'Rfc' => $emisor['rfc'],
                'Name' => $emisor['nombre'],
            ],
            'Receiver' => [
                'Rfc' => $receptor['rfc'],
                'CfdiUse' => 'G03',
                'Name' => $receptor['nombre'],
                'FiscalRegime' => $receptor['regimen_fiscal'],
                'TaxZipCode' => $receptor['codigo_postal'],
            ],
            'Relations' => [
                'Type' => $motivoConfig['tipo_relacion'],
                'Cfdis' => [
                    ['Uuid' => $factura->Uuid],
                ],
            ],
            'Items' => [
                [
                    'Quantity' => '1',
                    'ProductCode' => '84111506',
                    'UnitCode' => 'ACT',
                    'Unit' => 'Actividad',
                    'Description' => $this->sanitizarDescripcionCfdi($descripcion),
                    'UnitPrice' => number_format($subtotal, 2, '.', ''),
                    'Subtotal' => number_format($subtotal, 2, '.', ''),
                    'TaxObject' => '02',
                    'Taxes' => [
                        [
                            'Name' => 'IVA',
                            'Rate' => '0.16',
                            'Total' => number_format($iva, 2, '.', ''),
                            'Base' => number_format($subtotal, 2, '.', ''),
                            'IsRetention' => 'false',
                            'IsFederalTax' => 'true',
                        ],
                    ],
                    'Total' => number_format($total, 2, '.', ''),
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

            Log::error('Error al timbrar nota de débito', [
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

    private function sanitizarDescripcionCfdi(string $descripcion): string
    {
        $descripcion = str_replace('|', '-', $descripcion);
        $descripcion = preg_replace('/\s+/u', ' ', trim($descripcion)) ?? '';

        if ($descripcion === '') {
            return 'Nota de débito';
        }

        return mb_substr($descripcion, 0, 1000);
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
