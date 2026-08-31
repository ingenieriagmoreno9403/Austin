<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SatVerificationService
{
    protected $client;
    protected $wsdlUrl = 'https://consultaqr.facturaelectronica.sat.gob.mx/ConsultaCFDIService.svc';

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://consultaqr.facturaelectronica.sat.gob.mx/',
            'verify' => storage_path('certs/sat_cert.pem'), // Certificado SAT
            'timeout' => 15,
            'connect_timeout' => 5,
            'http_errors' => false
        ]);
    }

    public function validateCfdi(array $cfdiData): array
    {
        try {
            $response = $this->client->post('ConsultaCFDIService.svc', [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => 'http://tempuri.org/IConsultaCFDIService/Consulta',
                    'Cache-Control' => 'no-cache'
                ],
                'body' => $this->createValidSoapRequest($cfdiData)
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("Error en la respuesta del SAT. Código: $statusCode");
            }

            return $this->parseSoapResponse($responseBody);

        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Error de conexión con el SAT: ' . $e->getMessage(),
                'codigo' => 'CONNECTION_ERROR'
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'codigo' => 'VALIDATION_ERROR'
            ];
        }
    }

    private function createValidSoapRequest(array $data): string
    {
        $expression = urlencode(implode('&', [
            "re={$data['rfc_emisor']}",
            "rr={$data['rfc_receptor']}",
            "tt={$data['total']}",
            "id={$data['uuid']}"
        ]));

        return <<<XML
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/">
           <soapenv:Header/>
           <soapenv:Body>
              <tem:Consulta>
                 <tem:expresionImpresa>$expression</tem:expresionImpresa>
              </tem:Consulta>
           </soapenv:Body>
        </soapenv:Envelope>
        XML;
    }

    private function parseSoapResponse(string $response): array
    {
        try {
            $xml = new \SimpleXMLElement($response);
            $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xml->registerXPathNamespace('ns', 'http://tempuri.org/');

            $result = $xml->xpath('//ns:ConsultaResult');
            
            if (empty($result)) {
                throw new \Exception("Respuesta del SAT no contiene resultado válido");
            }

            $status = (string)$result[0];

            return [
                'error' => false,
                'codigo' => $status,
                'estado' => $this->translateStatus($status),
                'respuesta_completa' => $response
            ];

        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Error al procesar respuesta: ' . $e->getMessage(),
                'codigo' => 'PARSE_ERROR',
                'respuesta_completa' => $response
            ];
        }
    }

    private function translateStatus(string $status): string
    {
        $status = strtoupper(trim($status));
        
        $statusMap = [
            'VIGENTE' => 'Válido',
            'CANCELADO' => 'Cancelado',
            'NO ENCONTRADO' => 'No existe en SAT',
            '1' => 'Válido',
            '2' => 'Cancelado',
            '0' => 'No válido'
        ];

        return $statusMap[$status] ?? "Estado desconocido ($status)";
    }
}