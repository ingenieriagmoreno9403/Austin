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
    public function gastoRealPorNombres(string $empresa, int $year, array $nombres, int $maxPages = 12, int $concurrency = 8, string $cc = ''): array
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
        $cc = trim($cc);
        if ($cc !== '') {
            $base['CC'] = $cc;
        }
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
     * Una consulta por par centro + cuenta (FormatCode).
     * El filtro CC/Cuenta de la API es parcial; el llamador se queda con el par exacto.
     * Un HTTP 429 no cuenta como “sin gasto”.
     *
     * @param  array<int, string>  $cuentas
     * @return array{ok: bool, message: string|null, rows: array<int, array<string, mixed>>, failed: array<int, string>}
     */
    public function gastoRealPorCuentas(string $empresa, int $year, array $cuentas, int $maxPages = 12, int $concurrency = 3, string $cc = ''): array
    {
        $cuentas = array_values(array_unique(array_filter(array_map('trim', $cuentas))));
        if (! $cuentas) {
            return ['ok' => true, 'message' => null, 'rows' => [], 'failed' => []];
        }

        $base = [
            'Empresa' => $empresa,
            'year' => $year,
            'fecha_desde' => $year . '-01-01',
            'fecha_hasta' => $year . '-12-31',
            'per_page' => 500,
        ];
        $cc = trim($cc);
        if ($cc !== '') {
            $base['CC'] = $cc;
        }
        $url = $this->baseUrl.'/gasto-real';
        $rows = [];
        $lastByCuenta = [];
        $failed = [];
        $message = null;

        $take = function ($response, $i) use (&$rows, &$lastByCuenta, &$failed, $maxPages, $cuentas) {
            $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 0;
            if ($status !== 200) {
                $failed[$i] = $cuentas[$i] ?? (string) $i;

                return;
            }
            unset($failed[$i]);
            $json = json_decode((string) $response->getBody(), true);
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];
            foreach ($data as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
            $meta = is_array($json['meta'] ?? null) ? $json['meta'] : [];
            $last = (int) ($meta['last_page'] ?? 1);
            $lastByCuenta[$i] = min(max(1, $last), max(1, $maxPages));
        };

        $runPool = function (callable $requests) use ($concurrency, $take, &$message) {
            $pool = new Pool($this->client, $requests(), [
                'concurrency' => max(1, $concurrency),
                'fulfilled' => function ($response, $i) use ($take) {
                    $take($response, $i);
                },
                'rejected' => function ($reason, $i) use (&$failed, &$message, $cuentas) {
                    $failed[$i] = $cuentas[$i] ?? (string) $i;
                    $message = $message ?: ('No se pudo conectar con AutinApi: ' . $reason);
                },
            ]);
            $pool->promise()->wait();
        };

        $runPool(function () use ($url, $base, $cuentas) {
            foreach ($cuentas as $i => $cuenta) {
                $query = array_filter(array_merge($base, ['Cuenta' => $cuenta, 'CC' => $base['CC'] ?? null, 'page' => 1]), static function ($value) {
                    return $value !== null && $value !== '';
                });
                yield $i => new Request('GET', $url.'?'.http_build_query($query));
            }
        });

        if ($failed) {
            $retry = $failed;
            $runPool(function () use ($url, $base, $retry) {
                foreach ($retry as $i => $cuenta) {
                    $query = array_filter(array_merge($base, ['Cuenta' => $cuenta, 'CC' => $base['CC'] ?? null, 'page' => 1]), static function ($value) {
                        return $value !== null && $value !== '';
                    });
                    yield $i => new Request('GET', $url.'?'.http_build_query($query));
                }
            });
        }

        $pageReqs = function () use ($url, $base, $cuentas, $lastByCuenta) {
            foreach ($cuentas as $i => $cuenta) {
                if (! isset($lastByCuenta[$i])) {
                    continue;
                }
                $last = (int) $lastByCuenta[$i];
                for ($page = 2; $page <= $last; $page++) {
                    $query = array_filter(array_merge($base, ['Cuenta' => $cuenta, 'CC' => $base['CC'] ?? null, 'page' => $page]), static function ($value) {
                        return $value !== null && $value !== '';
                    });
                    yield $i . '-' . $page => new Request('GET', $url.'?'.http_build_query($query));
                }
            }
        };
        $pageFailed = [];
        $poolPages = new Pool($this->client, $pageReqs(), [
            'concurrency' => max(1, $concurrency),
            'fulfilled' => function ($response, $key) use (&$rows, &$pageFailed) {
                if ($response->getStatusCode() !== 200) {
                    $pageFailed[$key] = true;

                    return;
                }
                unset($pageFailed[$key]);
                $json = json_decode((string) $response->getBody(), true);
                $data = is_array($json['data'] ?? null) ? $json['data'] : [];
                foreach ($data as $row) {
                    if (is_array($row)) {
                        $rows[] = $row;
                    }
                }
            },
            'rejected' => function ($reason, $key) use (&$pageFailed, &$message) {
                $pageFailed[$key] = true;
                $message = $message ?: ('No se pudo conectar con AutinApi: ' . $reason);
            },
        ]);
        $poolPages->promise()->wait();
        if ($pageFailed) {
            $retryPages = function () use ($pageReqs, $pageFailed) {
                foreach ($pageReqs() as $key => $request) {
                    if (isset($pageFailed[$key])) {
                        yield $key => $request;
                    }
                }
            };
            $poolRetry = new Pool($this->client, $retryPages(), [
                'concurrency' => max(1, $concurrency),
                'fulfilled' => function ($response) use (&$rows) {
                    if ($response->getStatusCode() !== 200) {
                        return;
                    }
                    $json = json_decode((string) $response->getBody(), true);
                    $data = is_array($json['data'] ?? null) ? $json['data'] : [];
                    foreach ($data as $row) {
                        if (is_array($row)) {
                            $rows[] = $row;
                        }
                    }
                },
            ]);
            $poolRetry->promise()->wait();
        }

        $failedCodes = array_values(array_unique(array_filter($failed)));

        return [
            'ok' => $failedCodes === [] || $lastByCuenta !== [],
            'message' => ($failedCodes === [] || $lastByCuenta !== []) ? null : ($message ?: 'Sin conexión a gasto real SAP'),
            'rows' => $rows,
            'failed' => $failedCodes,
        ];
    }

    /**
     * Una fila de gasto-real por centro para leer DEPTO.
     *
     * @param  array<int, string>  $ccs
     * @return array{ok: bool, message: string|null, por_cc: array<string, string>}
     */
    public function gastoRealDeptoPorCentros(string $empresa, int $year, array $ccs, int $concurrency = 8): array
    {
        $ccs = array_values(array_unique(array_filter(array_map('trim', $ccs))));
        if (! $ccs) {
            return ['ok' => true, 'message' => null, 'por_cc' => []];
        }

        $url = $this->baseUrl.'/gasto-real';
        $base = [
            'Empresa' => $empresa,
            'year' => $year,
            'fecha_desde' => $year . '-01-01',
            'fecha_hasta' => $year . '-12-31',
            'per_page' => 1,
            'page' => 1,
        ];
        $porCc = [];
        $ok = false;
        $message = null;

        $reqs = function () use ($url, $base, $ccs) {
            foreach ($ccs as $i => $cc) {
                $query = array_filter(array_merge($base, ['CC' => $cc]));
                yield $i => new Request('GET', $url.'?'.http_build_query($query));
            }
        };
        $pool = new Pool($this->client, $reqs(), [
            'concurrency' => max(1, $concurrency),
            'fulfilled' => function ($response, $i) use (&$porCc, &$ok, $ccs) {
                $ok = true;
                $json = json_decode((string) $response->getBody(), true);
                $data = is_array($json['data'] ?? null) ? $json['data'] : [];
                $row = is_array($data[0] ?? null) ? $data[0] : [];
                $depto = trim((string) ($row['DEPTO'] ?? $row['Depto'] ?? $row['depto'] ?? $row['departamento'] ?? ''));
                if ($depto === '' || preg_match('/^\d+$/', $depto)) {
                    return;
                }
                $cc = (string) ($ccs[$i] ?? '');
                if ($cc !== '') {
                    $porCc[$cc] = $depto;
                }
                $rowCc = trim((string) ($row['CC'] ?? $row['PrcCode'] ?? ''));
                if ($rowCc !== '') {
                    $porCc[$rowCc] = $depto;
                }
            },
            'rejected' => function ($reason) use (&$message) {
                $message = $message ?: ('No se pudo conectar con AutinApi: ' . $reason);
            },
        ]);
        $pool->promise()->wait();

        return [
            'ok' => $ok,
            'message' => $ok ? null : ($message ?: 'Sin conexión a gasto real SAP'),
            'por_cc' => $porCc,
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
     * Listas de precios por empresa / cliente.
     * Filtros: Empresa, CodigoCliente, per_page, page.
     *
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    public function listasPrecios(array $filters = []): array
    {
        return $this->request('GET', 'listas-precios', $filters);
    }

    /**
     * Lista de precios de venta (OCRD + OPLN + ITM1 + OITM).
     * Filtros: Empresa, CodigoCliente, CodigoArticulo, NoLista, Moneda, per_page, page.
     *
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    public function listaPreciosVenta(array $filters = []): array
    {
        return $this->request('GET', 'listaPreciosventa', $filters);
    }

    /**
     * Varios artículos de listaPreciosventa en paralelo (tope bajo para no saturar AutinApi).
     *
     * @param  array<int, string>  $articulos
     * @return array{ok: bool, message: string|null, por_item: array<string, array<int, array<string, mixed>>>}
     */
    public function listaPreciosPorArticulos(string $empresa, string $cliente, array $articulos, int $concurrency = 3): array
    {
        $articulos = array_values(array_unique(array_filter(array_map(static function ($v) {
            return trim((string) $v);
        }, $articulos))));
        if (! $articulos) {
            return ['ok' => true, 'message' => null, 'por_item' => []];
        }

        $url = $this->baseUrl.'/listaPreciosventa';
        $requests = function () use ($url, $empresa, $cliente, $articulos) {
            foreach ($articulos as $i => $item) {
                $query = array_filter([
                    'Empresa' => $empresa,
                    'CodigoCliente' => $cliente,
                    'CodigoArticulo' => $item,
                    'per_page' => 100,
                    'page' => 1,
                ], static function ($value) {
                    return $value !== null && $value !== '';
                });
                yield $i => new Request('GET', $url.'?'.http_build_query($query));
            }
        };

        $porItem = [];
        $retry = [];
        $anyOk = false;
        $message = null;
        $pool = new Pool($this->client, $requests(), [
            'concurrency' => max(1, min(3, $concurrency)),
            'fulfilled' => function ($response, $index) use (&$porItem, &$retry, &$anyOk, &$message, $articulos) {
                $item = $articulos[$index] ?? '';
                if ((int) $response->getStatusCode() === 429) {
                    $retry[] = (int) $index;
                    if ($message === null) {
                        $message = 'Too Many Attempts.';
                    }

                    return;
                }
                if ((int) $response->getStatusCode() < 200 || (int) $response->getStatusCode() >= 300) {
                    $retry[] = (int) $index;

                    return;
                }
                $json = json_decode((string) $response->getBody(), true);
                $data = is_array($json['data'] ?? null) ? $json['data'] : [];
                $anyOk = true;
                if ($item !== '') {
                    $porItem[strtoupper($item)] = $data;
                }
            },
            'rejected' => function ($reason, $index) use (&$retry) {
                $retry[] = (int) $index;
            },
        ]);
        $pool->promise()->wait();

        if ($retry) {
            usleep(800000);
            foreach ($retry as $index) {
                $item = $articulos[$index] ?? '';
                if ($item === '') {
                    continue;
                }
                $res = $this->listaPreciosVenta([
                    'Empresa' => $empresa,
                    'CodigoCliente' => $cliente,
                    'CodigoArticulo' => $item,
                    'per_page' => 100,
                    'page' => 1,
                ]);
                if (! empty($res['ok'])) {
                    $anyOk = true;
                    $body = is_array($res['body'] ?? null) ? $res['body'] : [];
                    $porItem[strtoupper($item)] = is_array($body['data'] ?? null) ? $body['data'] : [];
                } elseif ($message === null) {
                    $message = $res['message'] ?? null;
                }
            }
        }

        return [
            'ok' => $anyOk,
            'message' => $anyOk ? null : $message,
            'por_item' => $porItem,
        ];
    }

    /**
     * Precios mensuales por empresa / cliente / artículo.
     * Filtros: year, Empresa, CardCode, Mes, ItemCode, per_page, page.
     * Campos: Empresa, CardCode, CardName, ItemCode, Mes, Precio.
     *
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    public function preciosMensuales(array $filters = []): array
    {
        return $this->request('GET', 'precios-mensuales', $filters);
    }

    /**
     * Ventas budget (últimos meses / proyección).
     * Filtros: Empresa, CardCode, ItemCode, meses (ej. "10,11,12"), per_page, page.
     *
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, status: int, body: array|null, message: string|null}
     */
    public function ventasBudget(array $filters = []): array
    {
        return $this->request('GET', 'ventas-budget', $filters);
    }

    /**
     * Recorre páginas de /ventas-budget.
     *
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, message: string|null, rows: array<int, array<string, mixed>>}
     */
    public function ventasBudgetTodasPaginas(array $filters, int $maxPages = 30, int $concurrency = 6): array
    {
        $filters['per_page'] = 500;
        $first = $this->ventasBudget(array_merge($filters, ['page' => 1]));
        if (empty($first['ok'])) {
            return [
                'ok' => false,
                'message' => $first['message'] ?? 'Sin conexión a ventas-budget',
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

        $url = $this->baseUrl.'/ventas-budget';
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
     * Recorre páginas de /ventas.
     * per_page alto (p. ej. 500) suele provocar 504 en AutinApi; por defecto 80.
     *
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, message: string|null, rows: array<int, array<string, mixed>>}
     */
    public function ventasTodasPaginas(array $filters, int $maxPages = 30, int $concurrency = 6, int $perPage = 80): array
    {
        $perPage = max(20, min(120, $perPage));
        $filters['per_page'] = $perPage;
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
        $retryPages = [];
        $pool = new Pool($this->client, $requests(), [
            'concurrency' => max(1, min(3, $concurrency)),
            'fulfilled' => function ($response, $page) use (&$extra, &$retryPages) {
                if ((int) $response->getStatusCode() === 429) {
                    $retryPages[] = (int) $page;

                    return;
                }
                $json = json_decode((string) $response->getBody(), true);
                $data = is_array($json['data'] ?? null) ? $json['data'] : [];
                foreach ($data as $row) {
                    if (is_array($row)) {
                        $extra[] = $row;
                    }
                }
            },
            'rejected' => function ($reason, $page) use (&$retryPages) {
                $retryPages[] = (int) $page;
            },
        ]);
        $pool->promise()->wait();

        if ($retryPages) {
            usleep(800000);
            foreach ($retryPages as $page) {
                $again = $this->ventas(array_merge($filters, ['page' => $page]));
                if (empty($again['ok'])) {
                    continue;
                }
                $againBody = is_array($again['body'] ?? null) ? $again['body'] : [];
                $data = is_array($againBody['data'] ?? null) ? $againBody['data'] : [];
                foreach ($data as $row) {
                    if (is_array($row)) {
                        $extra[] = $row;
                    }
                }
            }
        }

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
    protected function request(string $method, string $uri, array $query = [], bool $retried = false): array
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

            if ($status === 429 && ! $retried) {
                usleep(700000);

                return $this->request($method, $uri, $query, true);
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
