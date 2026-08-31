<?php

namespace App\Traits;

use App\Models\DatosFiscalesEmpresa;
use App\Models\Clientes;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

trait FacturamaTraits
{
    /**
     * Extrae un mensaje legible del error devuelto por la API de Facturama.
     */
    protected function extraerErrorFacturama(\Throwable $e): string
    {
        if ($e instanceof RequestException && $e->hasResponse()) {
            $response = $e->getResponse();
            $body = $response->getBody();

            if ($body->isSeekable()) {
                $body->rewind();
            }

            $raw = $body->getContents();

            if ($raw === '' && method_exists($body, '__toString')) {
                $raw = (string) $body;
            }

            if ($raw !== '') {
                return $this->formatearRespuestaErrorFacturama($raw);
            }

            return 'HTTP ' . $response->getStatusCode() . ' sin cuerpo de respuesta';
        }

        return $e->getMessage() ?: 'Error desconocido al comunicarse con Facturama';
    }

    /**
     * Parsea JSON o texto plano de error de Facturama.
     */
    protected function formatearRespuestaErrorFacturama(string $raw): string
    {
        $json = json_decode($raw, true);

        if (!is_array($json)) {
            return trim($raw);
        }

        $partes = [];

        foreach (['Message', 'message', 'error', 'Error', 'Detail', 'detail'] as $key) {
            if (!empty($json[$key]) && is_string($json[$key])) {
                $partes[] = $json[$key];
            }
        }

        if (!empty($json['ModelState']) && is_array($json['ModelState'])) {
            foreach ($json['ModelState'] as $campo => $mensajes) {
                if (is_array($mensajes)) {
                    foreach ($mensajes as $msg) {
                        $partes[] = is_string($msg) ? "{$campo}: {$msg}" : "{$campo}: " . json_encode($msg);
                    }
                } elseif (is_string($mensajes)) {
                    $partes[] = "{$campo}: {$mensajes}";
                }
            }
        }

        if (!empty($json['Details']) && is_array($json['Details'])) {
            foreach ($json['Details'] as $det) {
                $partes[] = is_string($det) ? $det : json_encode($det, JSON_UNESCAPED_UNICODE);
            }
        }

        if (!empty($json['errors']) && is_array($json['errors'])) {
            foreach ($json['errors'] as $campo => $mensajes) {
                if (is_array($mensajes)) {
                    foreach ($mensajes as $msg) {
                        $partes[] = is_string($msg) ? "{$campo}: {$msg}" : json_encode($msg);
                    }
                }
            }
        }

        $partes = array_values(array_unique(array_filter($partes)));

        if (!empty($partes)) {
            return implode("\n", $partes);
        }

        return trim($raw);
    }

    /**
     * Datos fiscales del emisor desde tbl_datos_fiscales_empresa (registro ACTIVO).
     */
    protected function obtenerDatosEmisorFiscal(): array
    {
        $fiscal = DatosFiscalesEmpresa::query()
            ->where('estatus', 'ACTIVO')
            ->orderByDesc('id')
            ->first();

        if (!$fiscal) {
            $fiscal = DatosFiscalesEmpresa::query()->orderByDesc('id')->first();
        }

        if ($fiscal) {
            $partesDireccion = array_filter([
                trim(($fiscal->calle ?? '') . ' ' . ($fiscal->numero_exterior ?? '')),
                !empty($fiscal->numero_interior) ? 'Int. ' . $fiscal->numero_interior : null,
                $fiscal->colonia ?? null,
                $fiscal->municipio ?? null,
                $fiscal->estado ?? null,
                $fiscal->pais ?? 'Mexico',
            ]);
            $direccion = implode(', ', $partesDireccion);
            if (!empty($fiscal->codigo_postal)) {
                $direccion .= ($direccion ? '. ' : '') . 'C.P. ' . $fiscal->codigo_postal;
            }

            $regimenCodigo = trim((string) ($fiscal->regimen_fiscal ?? '601'));
            $regimenTexto = $regimenCodigo;
            if (!empty($fiscal->regimen_fiscal_descripcion)) {
                $regimenTexto .= ' - ' . $fiscal->regimen_fiscal_descripcion;
            }

            return [
                'nombre' => $fiscal->razon_social ?? '',
                'rfc' => $fiscal->rfc ?? '',
                'direccion' => $direccion,
                'lugar_expedicion' => $fiscal->codigo_postal ?? '',
                'regimen_fiscal' => $regimenCodigo,
                'regimen_fiscal_texto' => $regimenTexto,
                'telefono' => $fiscal->telefono ?? '',
                'correo' => $fiscal->correo ?? '',
            ];
        }

        return [
            'nombre' => env('FACTURAMA_EMISOR_NOMBRE', 'UMMININGN'),
            'rfc' => env('FACTURAMA_EMISOR_RFC', 'UMM200127ME3'),
            'direccion' => 'C.P. ' . env('FACTURAMA_EXPEDITION_PLACE', '27023'),
            'lugar_expedicion' => env('FACTURAMA_EXPEDITION_PLACE', '27023'),
            'regimen_fiscal' => env('FACTURAMA_EMISOR_REGIMEN', '601'),
            'regimen_fiscal_texto' => env('FACTURAMA_EMISOR_REGIMEN', '601') . ' - General de Ley Personas Morales',
            'telefono' => '',
            'correo' => '',
        ];
    }

    /**
     * Datos fiscales del receptor desde CFDI timbrado y catálogo de clientes.
     */
    protected function obtenerDatosReceptorFiscal(object $factura, ?array $cfdi = null): array
    {
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

    protected function formatearRegimenFiscal(string $codigo): string
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

    /**
     * Obtiene el PDF de un CFDI emitido desde Facturama (mismo flujo que verfacturaproductos).
     */
    protected function obtenerPdfBase64Facturama(string $facturamaId): string
    {
        $baseUri = rtrim((string) env('FACTURAMA_BASE_URI', 'https://apisandbox.facturama.mx'), '/') . '/';

        $client = new Client([
            'base_uri' => $baseUri,
            'auth' => [env('USER_FAC'), env('PWD')],
            'verify' => false,
            'timeout' => 60.0,
        ]);

        $response = $client->request('GET', "cfdi/pdf/issued/{$facturamaId}", [
            'headers' => ['Accept' => 'application/json'],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (empty($data['Content'])) {
            throw new \RuntimeException('Facturama no devolvió el contenido del PDF.');
        }

        return (string) $data['Content'];
    }

    protected function respuestaPdfDesdeBase64(string $base64, string $filename, bool $inline = true)
    {
        $base64 = preg_replace('/^data:application\/pdf;base64,/', '', $base64);
        $pdfData = base64_decode($base64, true);

        if ($pdfData === false || $pdfData === '') {
            throw new \RuntimeException('No se pudo decodificar el PDF recibido de Facturama.');
        }

        $disposition = ($inline ? 'inline' : 'attachment') . '; filename="' . $filename . '"';

        return response($pdfData, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition,
            'Content-Length' => strlen($pdfData),
        ]);
    }
}
