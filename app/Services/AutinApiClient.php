<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;

/**
 * Cliente HTTP hacia AutinApi (SQL Server / catálogos SAP).
 * La BD local MySQL del ERP no se toca aquí: solo llamadas a la API externa.
 */
class AutinApiClient
{
    protected Client $client;

    protected string $baseUrl;

    protected string $defaultDatabase;

    /** @var string[] */
    protected array $allowedDatabases = ['austin', 'imsa', 'pitic', 'sydney'];

    /** @var string[] */
    protected array $allowedResources = [
        'centros-costo',
        'cuentas',
        'agrupaciones-cuentas',
        'transacciones',
    ];

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.autin_api.base_url'), '/');
        $username = (string) config('services.autin_api.username');
        $password = (string) config('services.autin_api.password');
        $this->defaultDatabase = strtolower((string) config('services.autin_api.default_db', 'austin'));

        if ($this->baseUrl === '') {
            throw new RuntimeException('AUTIN_API_BASE_URL no está configurada.');
        }

        // No type-hint Guzzle Client en el constructor: el contenedor de Laravel
        // inyectaría uno sin auth y rompería Basic Auth.
        $this->client = new Client([
            'auth' => [$username, $password],
            'timeout' => (float) config('services.autin_api.timeout', 30),
            'connect_timeout' => (float) config('services.autin_api.connect_timeout', 10),
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function defaultDatabase(): string
    {
        return $this->defaultDatabase;
    }

    /**
     * @return string[]
     */
    public function allowedDatabases(): array
    {
        return $this->allowedDatabases;
    }

    /**
     * @return string[]
     */
    public function allowedResources(): array
    {
        return $this->allowedResources;
    }

    /**
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    public function health(): array
    {
        return $this->request('GET', 'health');
    }

    /**
     * Cuentas de todas las empresas (UNION AUSTIN/PITIC/SYDNEY/IMSA).
     *
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    public function cuentasGlobal(array $filters = []): array
    {
        return $this->request('GET', 'cuentas', $filters);
    }

    /**
     * Centros de costo de todas las empresas.
     *
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    public function centrosCostoGlobal(array $filters = []): array
    {
        return $this->request('GET', 'centros-costo', $filters);
    }

    /**
     * Gasto real histórico (consulta global AutinApi).
     * Filtros: Empresa, CC, Cuenta, DescCuenta, year, GroupMask, fecha_desde, fecha_hasta, per_page, page.
     *
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    public function gastoReal(array $filters = []): array
    {
        return $this->request('GET', 'gasto-real', $filters);
    }

    /**
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    public function catalogos(?string $database = null): array
    {
        $db = $this->resolveDatabase($database);

        return $this->request('GET', $db . '/catalogos');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    public function index(string $resource, array $filters = [], ?string $database = null): array
    {
        $db = $this->resolveDatabase($database);
        $resource = $this->resolveResource($resource);

        return $this->request('GET', $db . '/' . $resource, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    public function show(string $resource, string $id, array $filters = [], ?string $database = null): array
    {
        $db = $this->resolveDatabase($database);
        $resource = $this->resolveResource($resource);
        $id = rawurlencode($id);

        return $this->request('GET', $db . '/' . $resource . '/' . $id, $filters);
    }

    protected function resolveDatabase(?string $database): string
    {
        $db = strtolower($database ?: $this->defaultDatabase);

        if (! in_array($db, $this->allowedDatabases, true)) {
            throw new RuntimeException('Empresa/base no válida: ' . $db);
        }

        return $db;
    }

    protected function resolveResource(string $resource): string
    {
        $resource = strtolower(trim($resource));

        if (! in_array($resource, $this->allowedResources, true)) {
            throw new RuntimeException('Recurso no válido: ' . $resource);
        }

        return $resource;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    protected function request(string $method, string $uri, array $query = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($uri, '/');

        try {
            $response = $this->client->request($method, $url, [
                'query' => array_filter($query, static function ($value) {
                    return $value !== null && $value !== '';
                }),
            ]);

            $status = $response->getStatusCode();
            $raw = (string) $response->getBody();
            $body = json_decode($raw, true);

            if (! is_array($body)) {
                $body = $raw !== '' ? ['raw' => $raw] : null;
            }

            if ($status >= 200 && $status < 300) {
                return [
                    'ok' => true,
                    'status' => $status,
                    'body' => $body,
                    'message' => null,
                ];
            }

            $message = is_array($body)
                ? ($body['message'] ?? $body['error'] ?? ('Error HTTP ' . $status))
                : ('Error HTTP ' . $status);

            return [
                'ok' => false,
                'status' => $status,
                'body' => is_array($body) ? $body : null,
                'message' => is_string($message) ? $message : 'Error al consultar AutinApi',
            ];
        } catch (RequestException $e) {
            return [
                'ok' => false,
                'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0,
                'body' => null,
                'message' => 'No se pudo conectar con AutinApi: ' . $e->getMessage(),
            ];
        } catch (GuzzleException $e) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => null,
                'message' => 'Error de red con AutinApi: ' . $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => null,
                'message' => $e->getMessage(),
            ];
        }
    }
}
