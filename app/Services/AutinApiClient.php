<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
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
     * Filtros: Empresa, CC, Cuenta, DescCuenta, DEPTO, year, GroupMask, fecha_desde, fecha_hasta, per_page, page.
     *
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    public function gastoReal(array $filters = []): array
    {
        return $this->request('GET', 'gasto-real', $filters);
    }

    /**
     * Recorre páginas de /gasto-real (tope 500 por página).
     *
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, message: string|null, rows: array<int, array<string, mixed>>}
     */
    public function gastoRealTodasPaginas(array $filters, int $maxPages = 40, int $concurrency = 6): array
    {
        $filters['per_page'] = 500;
        $first = $this->gastoReal(array_merge($filters, ['page' => 1]));
        if (empty($first['ok'])) {
            return [
                'ok' => false,
                'message' => $first['message'] ?? 'Sin conexión a gasto real SAP',
                'rows' => [],
            ];
        }

        $body = is_array($first['body'] ?? null) ? $first['body'] : [];
        $rows = is_array($body['data'] ?? null) ? $body['data'] : [];
        $meta = is_array($body['meta'] ?? null) ? $body['meta'] : [];
        $last = (int) ($meta['last_page'] ?? $body['last_page'] ?? 1);
        if ($last < 1) {
            $last = 1;
        }
        $last = min($last, max(1, $maxPages));
        if ($last <= 1) {
            return ['ok' => true, 'message' => null, 'rows' => $rows];
        }

        $url = $this->baseUrl.'/gasto-real';
        $requests = function () use ($url, $filters, $last) {
            for ($page = 2; $page <= $last; $page++) {
                $query = array_filter(array_merge($filters, ['page' => $page]), static function ($value) {
                    return $value !== null && $value !== '';
                });
                yield $page => new Request('GET', $url.'?'.http_build_query($query));
            }
        };

        $extra = [];
        $pool = new Pool($this->client, $requests(), [
            'concurrency' => max(1, $concurrency),
            'fulfilled' => function ($response) use (&$extra) {
                $json = json_decode((string) $response->getBody(), true);
                $data = is_array($json['data'] ?? null) ? $json['data'] : [];
                foreach ($data as $row) {
                    if (is_array($row)) {
                        $extra[] = $row;
                    }
                }
            },
        ]);
        $pool->promise()->wait();

        return ['ok' => true, 'message' => null, 'rows' => array_merge($rows, $extra)];
    }

    /**
     * Varias DescCuenta en paralelo (página 1 de todas, luego el resto).
     *
     * @param  array<int, string>  $nombres
     * @return array{ok: bool, message: string|null, rows: array<int, array<string, mixed>>}
     */
    public function gastoRealPorNombres(string $empresa, int $year, array $nombres, int $maxPages = 12, int $concurrency = 8): array
    {
        $nombres = array_values(array_unique(array_filter(array_map('trim', $nombres))));
        if (! $nombres) {
            return ['ok' => true, 'message' => null, 'rows' => []];
        }

        $base = [
            'Empresa' => $empresa,
            'year' => $year,
            'fecha_desde' => $year . '-01-01',
            'fecha_hasta' => $year . '-12-31',
            'per_page' => 500,
        ];
        $url = $this->baseUrl.'/gasto-real';
        $rows = [];
        $lastByName = [];
        $ok = false;
        $message = null;

        $firstReqs = function () use ($url, $base, $nombres) {
            foreach ($nombres as $i => $nombre) {
                $query = array_filter(array_merge($base, ['DescCuenta' => $nombre, 'page' => 1]));
                yield $i => new Request('GET', $url.'?'.http_build_query($query));
            }
        };
        $pool = new Pool($this->client, $firstReqs(), [
            'concurrency' => max(1, $concurrency),
            'fulfilled' => function ($response, $i) use (&$rows, &$lastByName, &$ok, $maxPages) {
                $json = json_decode((string) $response->getBody(), true);
                $data = is_array($json['data'] ?? null) ? $json['data'] : [];
                foreach ($data as $row) {
                    if (is_array($row)) {
                        $rows[] = $row;
                    }
                }
                $meta = is_array($json['meta'] ?? null) ? $json['meta'] : [];
                $last = (int) ($meta['last_page'] ?? 1);
                $lastByName[$i] = min(max(1, $last), max(1, $maxPages));
                $ok = true;
            },
            'rejected' => function ($reason) use (&$message) {
                $message = $message ?: ('No se pudo conectar con AutinApi: ' . $reason);
            },
        ]);
        $pool->promise()->wait();

        $more = function () use ($url, $base, $nombres, $lastByName) {
            foreach ($nombres as $i => $nombre) {
                $last = (int) ($lastByName[$i] ?? 1);
                for ($page = 2; $page <= $last; $page++) {
                    $query = array_filter(array_merge($base, ['DescCuenta' => $nombre, 'page' => $page]));
                    yield $i . '-' . $page => new Request('GET', $url.'?'.http_build_query($query));
                }
            }
        };
        $pool2 = new Pool($this->client, $more(), [
            'concurrency' => max(1, $concurrency),
            'fulfilled' => function ($response) use (&$rows) {
                $json = json_decode((string) $response->getBody(), true);
                $data = is_array($json['data'] ?? null) ? $json['data'] : [];
                foreach ($data as $row) {
                    if (is_array($row)) {
                        $rows[] = $row;
                    }
                }
            },
        ]);
        $pool2->promise()->wait();

        return [
            'ok' => $ok,
            'message' => $ok ? null : ($message ?: 'Sin conexión a gasto real SAP'),
            'rows' => $rows,
        ];
    }

    /**
     * Ventas y notas de crédito (OINV + ORIN).
     * CardName = cliente, ItemName = producto.
     *
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    public function ventas(array $filters = []): array
    {
        return $this->request('GET', 'ventas', $filters);
    }

    /**
     * Recorre todas las páginas de /ventas (tope 500 por página en AutinApi).
     *
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, message: string|null, rows: array<int, array<string, mixed>>}
     */
    public function ventasTodasPaginas(array $filters, int $maxPages = 30, int $concurrency = 6): array
    {
        $filters['per_page'] = 500;
        $first = $this->ventas(array_merge($filters, ['page' => 1]));
        if (empty($first['ok'])) {
            return [
                'ok' => false,
                'message' => $first['message'] ?? 'Sin conexión a ventas SAP',
                'rows' => [],
            ];
        }

        $body = is_array($first['body'] ?? null) ? $first['body'] : [];
        $rows = is_array($body['data'] ?? null) ? $body['data'] : [];
        $meta = is_array($body['meta'] ?? null) ? $body['meta'] : [];
        $last = (int) ($meta['last_page'] ?? 1);
        if ($last < 1) {
            $last = 1;
        }
        $last = min($last, max(1, $maxPages));
        if ($last <= 1) {
            return ['ok' => true, 'message' => null, 'rows' => $rows];
        }

        $url = $this->baseUrl.'/ventas';
        $requests = function () use ($url, $filters, $last) {
            for ($page = 2; $page <= $last; $page++) {
                $query = array_filter(array_merge($filters, ['page' => $page]), static function ($value) {
                    return $value !== null && $value !== '';
                });
                yield $page => new Request('GET', $url.'?'.http_build_query($query));
            }
        };

        $extra = [];
        $pool = new Pool($this->client, $requests(), [
            'concurrency' => max(1, $concurrency),
            'fulfilled' => function ($response) use (&$extra) {
                $json = json_decode((string) $response->getBody(), true);
                $data = is_array($json['data'] ?? null) ? $json['data'] : [];
                foreach ($data as $row) {
                    if (is_array($row)) {
                        $extra[] = $row;
                    }
                }
            },
        ]);
        $pool->promise()->wait();

        return ['ok' => true, 'message' => null, 'rows' => array_merge($rows, $extra)];
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
