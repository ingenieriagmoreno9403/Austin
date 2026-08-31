<?php

namespace App\Http\Controllers;

use App\Models\NotaCredito;
use App\Models\facturacionproductos;
use App\Models\Clientes;
use App\Traits\MenuTrait;
use App\Traits\DatosimpleTraits;
use App\Traits\SistemasTraits;
use App\Traits\GlobalTraits;
use App\Traits\FacturamaTraits;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotasCreditoController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use GlobalTraits;
    use FacturamaTraits;

    private const MOTIVOS = [
        'devolucion_mercancia' => [
            'label' => 'Devolución de Mercancía',
            'descripcion' => 'Devolución de mercancía por parte del cliente',
            'tipo_relacion' => '03',
        ],
        'descuento_bonificacion' => [
            'label' => 'Descuento o Bonificación Posterior',
            'descripcion' => 'Descuento o bonificación aplicada después de la factura original',
            'tipo_relacion' => '01',
        ],
        'correccion_errores' => [
            'label' => 'Corrección de Errores',
            'descripcion' => 'Corrección de montos o cantidades incorrectas en la factura original',
            'tipo_relacion' => '01',
        ],
        'cancelacion_parcial' => [
            'label' => 'Cancelación Parcial de la Venta',
            'descripcion' => 'Cancelación parcial de la venta facturada',
            'tipo_relacion' => '01',
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
            $permisoGestion = $this->forpermisos('gestion_notas_credito');

            $facturasTimbradas = facturacionproductos::query()
                ->whereNotNull('Uuid')
                ->where('Uuid', '!=', '')
                ->where('cancelada', 'A')
                ->where('metodo_pago', 'PUE')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();

            $notasCredito = NotaCredito::orderByDesc('created_at')->get();
            $motivos = self::MOTIVOS;
            $emisor = $this->obtenerDatosEmisorFiscal();

            return view('Tesoreria.NotasCredito.index', compact(
                'varpantallas',
                'varsubmenus',
                'permisoGestion',
                'facturasTimbradas',
                'notasCredito',
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

        $notasPrevias = NotaCredito::where('id_factura_original', $id)
            ->where('estado', 'Timbrada')
            ->sum('total');

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
            'notas_credito_aplicadas' => (float) $notasPrevias,
            'saldo_disponible' => max(0, round(((float) $factura->total) - (float) $notasPrevias, 2)),
            'cfdi_original' => $cfdiOriginal,
        ]);
    }

    public function emitir(Request $request)
    {
        if ($this->forpermisos('gestion_notas_credito') !== 'gestion_notas_credito') {
            return response()->json(['success' => false, 'message' => 'Sin permisos para emitir notas de crédito'], 403);
        }

        $request->validate([
            'factura_id' => 'required|integer|exists:tblfacturacionproductos,id',
            'motivo' => 'required|in:' . implode(',', array_keys(self::MOTIVOS)),
            'tipo_ajuste' => 'required|in:total,parcial',
            'monto' => 'required|numeric|min:0.01',
            'descripcion_adicional' => 'nullable|string|max:500',
        ]);

        $factura = facturacionproductos::find($request->factura_id);

        if (!$factura || empty($factura->Uuid) || $factura->cancelada === 'C') {
            return response()->json([
                'success' => false,
                'message' => 'La factura original no es válida para generar nota de crédito',
            ], 422);
        }

        if (strtoupper((string) $factura->metodo_pago) !== 'PUE') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden emitir notas de crédito sobre facturas con método de pago PUE',
            ], 422);
        }

        $motivoConfig = self::MOTIVOS[$request->motivo];
        $totalFactura = (float) $factura->total;
        $notasPrevias = (float) NotaCredito::where('id_factura_original', $factura->id)
            ->where('estado', 'Timbrada')
            ->sum('total');

        $saldoDisponible = round($totalFactura - $notasPrevias, 2);
        $montoSolicitado = round((float) $request->monto, 2);

        if ($request->tipo_ajuste === 'total') {
            $montoSolicitado = $saldoDisponible;
        }

        if ($montoSolicitado <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No hay saldo disponible en la factura para aplicar la nota de crédito',
            ], 422);
        }

        if ($montoSolicitado > $saldoDisponible + 0.01) {
            return response()->json([
                'success' => false,
                'message' => "El monto excede el saldo disponible de la factura (\$" . number_format($saldoDisponible, 2) . ')',
            ], 422);
        }

        $subtotal = round($montoSolicitado / 1.16, 2);
        $iva = round($montoSolicitado - $subtotal, 2);
        $total = round($montoSolicitado, 2);

        $descripcionMotivo = $motivoConfig['descripcion'];
        if ($request->filled('descripcion_adicional')) {
            $descripcionMotivo .= ' - ' . trim($request->descripcion_adicional);
        }
        $descripcionMotivo .= ' - Factura relacionada: ' . $factura->folio . ' (UUID: ' . $factura->Uuid . ')';

        $nota = NotaCredito::create([
            'id_factura_original' => $factura->id,
            'factura_uuid' => $factura->Uuid,
            'folio_factura' => $factura->folio,
            'motivo' => $request->motivo,
            'motivo_descripcion' => $descripcionMotivo,
            'tipo_relacion' => $motivoConfig['tipo_relacion'],
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
            'receptor_rfc' => $factura->reciver_rfc,
            'receptor_nombre' => $factura->reciver_nombre,
            'estado' => 'Pendiente',
            'created_by' => auth()->user()->name ?? 'system',
        ]);

        $resultado = $this->timbrarNotaCredito($nota, $factura, $motivoConfig, $descripcionMotivo, $subtotal, $iva, $total);

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
            'message' => 'Nota de crédito timbrada correctamente',
            'nota' => $nota->fresh(),
        ]);
    }

    public function verFactura(int $id)
    {
        $nota = NotaCredito::findOrFail($id);

        if (empty($nota->facturama_id)) {
            return back()->with('warning', 'La nota de crédito no tiene identificador de Facturama');
        }

        try {
            $base64 = $this->obtenerPdfBase64Facturama($nota->facturama_id);
            $filename = 'NotaCredito_' . ($nota->folio_nota ?? $nota->id) . '.pdf';

            return $this->respuestaPdfDesdeBase64($base64, $filename, true);
        } catch (\Exception $e) {
            Log::error('Error al ver PDF nota de crédito', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('warning', 'No se pudo abrir el PDF de la nota de crédito: ' . $e->getMessage());
        }
    }

    public function descargarPdf(int $id)
    {
        $nota = NotaCredito::findOrFail($id);

        if (empty($nota->facturama_id)) {
            return back()->with('warning', 'La nota de crédito no tiene identificador de Facturama');
        }

        try {
            $base64 = $this->obtenerPdfBase64Facturama($nota->facturama_id);
            $filename = 'NotaCredito_' . ($nota->folio_nota ?? $nota->id) . '.pdf';

            return $this->respuestaPdfDesdeBase64($base64, $filename, false);
        } catch (\Exception $e) {
            Log::error('Error al descargar PDF nota de crédito', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('warning', 'No se pudo descargar el PDF de la nota de crédito: ' . $e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        if ($this->forpermisos('gestion_notas_credito') !== 'gestion_notas_credito') {
            return response()->json([
                'success' => false,
                'message' => 'Sin permisos para eliminar notas de crédito',
            ], 403);
        }

        $nota = NotaCredito::find($id);

        if (!$nota) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado',
            ], 404);
        }

        $nota->delete();

        return response()->json([
            'success' => true,
            'message' => 'Registro de nota de crédito eliminado correctamente.',
        ]);
    }

    private function timbrarNotaCredito(
        NotaCredito $nota,
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
            'CfdiType' => 'E',
            'NameId' => '2',
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
                'CfdiUse' => 'G02',
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

            $datos = json_decode($response->getBody()->getContents(), true);

            return ['success' => true, 'data' => $datos];
        } catch (RequestException $e) {
            $errorDetalle = $this->extraerErrorFacturama($e);

            Log::error('Error al timbrar nota de crédito', [
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
        $datos = $this->obtenerDatosReceptorFiscal($factura);

        return [
            'rfc' => $datos['rfc'],
            'nombre' => $datos['nombre'],
            'regimen_fiscal' => $datos['regimen_fiscal'],
            'codigo_postal' => $datos['codigo_postal'],
        ];
    }

    private function obtenerDatosReceptorFiscal(object $factura, ?array $cfdi = null): array
    {
        if ($cfdi === null) {
            $cfdi = $this->obtenerCfdiFacturama($factura->facturama_id);
        }

        $receiver = is_array($cfdi) ? ($cfdi['Receiver'] ?? []) : [];
        $rfc = trim((string) ($receiver['Rfc'] ?? $factura->reciver_rfc ?? ''));
        $nombre = trim((string) ($receiver['Name'] ?? $factura->reciver_nombre ?? ''));
        $regimenCodigo = trim((string) ($receiver['FiscalRegime'] ?? ''));
        $codigoPostal = trim((string) ($receiver['TaxZipCode'] ?? ''));
        $usoCfdi = trim((string) ($receiver['CfdiUse'] ?? $cfdi['CfdiUse'] ?? ''));

        $cliente = $rfc !== ''
            ? Clientes::query()->where('rfc', $rfc)->first()
            : null;

        if ($codigoPostal === '' && $cliente) {
            $codigoPostal = trim((string) ($cliente->cp ?? ''));
        }

        if ($regimenCodigo === '' && $cliente) {
            $regimenCodigo = '601';
        } elseif ($regimenCodigo === '') {
            $regimenCodigo = '601';
        }

        $razonSocial = trim((string) ($cliente->razon_social ?? ''));
        if ($razonSocial === '') {
            $razonSocial = $nombre;
        }

        $partesDireccion = array_filter([
            trim(($cliente->calle ?? '') . ' ' . ($cliente->numero_ext ?? '')),
            !empty($cliente->numero_int) ? 'Int. ' . $cliente->numero_int : null,
            $cliente->colonia ?? null,
        ]);
        $direccion = implode(', ', $partesDireccion);
        if ($direccion !== '' && $codigoPostal !== '') {
            $direccion .= '. C.P. ' . $codigoPostal;
        } elseif ($codigoPostal !== '') {
            $direccion = 'C.P. ' . $codigoPostal;
        }

        return [
            'nombre' => $nombre,
            'razon_social' => $razonSocial,
            'rfc' => $rfc,
            'regimen_fiscal' => $regimenCodigo,
            'regimen_fiscal_texto' => $this->formatearRegimenFiscal($regimenCodigo),
            'codigo_postal' => $codigoPostal,
            'uso_cfdi' => $usoCfdi,
            'direccion' => $direccion,
            'telefono' => trim((string) ($cliente->telefono ?? '')),
            'correo' => trim((string) ($cliente->correo_electronico ?? '')),
        ];
    }

    private function formatearRegimenFiscal(string $codigo): string
    {
        $catalogo = [
            '601' => 'General de Ley Personas Morales',
            '603' => 'Personas Morales con Fines no Lucrativos',
            '605' => 'Sueldos y Salarios',
            '606' => 'Arrendamiento',
            '612' => 'Personas Físicas con Act. Empresariales',
            '616' => 'Sin obligaciones fiscales',
            '626' => 'Régimen Simplificado de Confianza',
        ];

        $codigo = trim($codigo);
        if ($codigo === '') {
            return '—';
        }

        return isset($catalogo[$codigo]) ? "{$codigo} - {$catalogo[$codigo]}" : $codigo;
    }

    private function sanitizarDescripcionCfdi(string $descripcion): string
    {
        $descripcion = str_replace('|', '-', $descripcion);
        $descripcion = preg_replace('/\s+/u', ' ', trim($descripcion)) ?? '';

        if ($descripcion === '') {
            return 'Nota de crédito';
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
