<?php

namespace App\Http\Controllers;

use App\Services\AutinApiClient;
use App\Traits\MenuTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Proxy autenticado hacia AutinApi.
 * MySQL local permanece para el resto del ERP; aquí solo datos SAP/SQL Server.
 */
class AutinApiController extends Controller
{
    use MenuTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(AutinApiClient $api)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $health = $api->health();
        $databases = $api->allowedDatabases();
        $resources = [
            'centros-costo' => 'Centros de costo (por empresa)',
            'cuentas' => 'Cuentas SAP (por empresa)',
            'agrupaciones-cuentas' => 'Agrupaciones de cuentas',
            'transacciones' => 'Transacciones / presupuesto',
        ];
        $globalResources = [
            'centros-costo' => 'Centros de costo (todas las empresas)',
            'cuentas' => 'Cuentas (todas las empresas)',
            'gasto-real' => 'Gasto real (histórico / GroupMask)',
        ];
        $defaultDb = $api->defaultDatabase();
        $apiBaseUrl = rtrim((string) config('services.autin_api.base_url'), '/');
        $proxyBaseUrl = url('/Sistemas/AutinApi');

        $endpoints = [
            [
                'grupo' => 'General',
                'method' => 'GET',
                'path' => '/health',
                'proxy' => $proxyBaseUrl . '/health',
                'remoto' => $apiBaseUrl . '/health',
                'descripcion' => 'Estado de la API y conexiones SQL Server (AUSTIN, IMSA, PITIC, SYDNEY).',
            ],
            [
                'grupo' => 'Global (todas las empresas)',
                'method' => 'GET',
                'path' => '/cuentas',
                'proxy' => $proxyBaseUrl . '/cuentas',
                'remoto' => $apiBaseUrl . '/cuentas',
                'descripcion' => 'Cuentas unificadas AUSTIN/PITIC/SYDNEY/IMSA. Filtros: Empresa, FormatCode, AcctName, GroupMask, per_page, page.',
            ],
            [
                'grupo' => 'Global (todas las empresas)',
                'method' => 'GET',
                'path' => '/centros-costo',
                'proxy' => $proxyBaseUrl . '/centros-costo',
                'remoto' => $apiBaseUrl . '/centros-costo',
                'descripcion' => 'Centros de costo unificados. Filtros: Empresa, PrcCode, PrcName, per_page, page.',
            ],
            [
                'grupo' => 'Global (todas las empresas)',
                'method' => 'GET',
                'path' => '/gasto-real',
                'proxy' => $proxyBaseUrl . '/gasto-real',
                'remoto' => $apiBaseUrl . '/gasto-real',
                'descripcion' => 'Gasto real histórico. Filtros: Empresa, CC, Cuenta, DescCuenta, year, GroupMask, fecha_desde, fecha_hasta, per_page, page.',
            ],
            [
                'grupo' => 'Por empresa',
                'method' => 'GET',
                'path' => '/{database}/catalogos',
                'proxy' => $proxyBaseUrl . '/austin/catalogos',
                'remoto' => $apiBaseUrl . '/austin/catalogos',
                'descripcion' => 'Lista de catálogos disponibles para la empresa.',
            ],
            [
                'grupo' => 'Por empresa',
                'method' => 'GET',
                'path' => '/{database}/centros-costo',
                'proxy' => $proxyBaseUrl . '/austin/centros-costo',
                'remoto' => $apiBaseUrl . '/austin/centros-costo',
                'descripcion' => 'Centros de costo (OPRC). Filtros: CC, NOMBRE, ESTATUS, per_page.',
            ],
            [
                'grupo' => 'Por empresa',
                'method' => 'GET',
                'path' => '/{database}/cuentas',
                'proxy' => $proxyBaseUrl . '/austin/cuentas',
                'remoto' => $apiBaseUrl . '/austin/cuentas',
                'descripcion' => 'Plan de cuentas SAP (OACT). Filtros: CUENTA, NOMBRE, GroupMask.',
            ],
            [
                'grupo' => 'Por empresa',
                'method' => 'GET',
                'path' => '/{database}/agrupaciones-cuentas',
                'proxy' => $proxyBaseUrl . '/austin/agrupaciones-cuentas',
                'remoto' => $apiBaseUrl . '/austin/agrupaciones-cuentas',
                'descripcion' => 'Agrupaciones por GroupMask. Filtro: GroupMask.',
            ],
            [
                'grupo' => 'Por empresa',
                'method' => 'GET',
                'path' => '/{database}/transacciones',
                'proxy' => $proxyBaseUrl . '/austin/transacciones',
                'remoto' => $apiBaseUrl . '/austin/transacciones',
                'descripcion' => 'Presupuesto / forecast (OBGT). Filtros: CC, Cuenta, Origen, Empresa.',
            ],
            [
                'grupo' => 'Por empresa',
                'method' => 'GET',
                'path' => '/{database}/{resource}/{id}',
                'proxy' => $proxyBaseUrl . '/austin/centros-costo/{id}',
                'remoto' => $apiBaseUrl . '/austin/centros-costo/{id}',
                'descripcion' => 'Detalle de un registro por ID (código de centro, cuenta, etc.).',
            ],
        ];

        $gastoRealFilters = [
            'Empresa' => 'Empresa SAP (AUSTIN, IMSA, PITIC, SYDNEY)',
            'CC' => 'Centro de costo',
            'Cuenta' => 'Código de cuenta',
            'DescCuenta' => 'Descripción de cuenta (texto)',
            'year' => 'Año (ej. 2026)',
            'GroupMask' => 'Máscara de agrupación (ej. 6)',
            'fecha_desde' => 'Fecha inicio (YYYY-MM-DD)',
            'fecha_hasta' => 'Fecha fin (YYYY-MM-DD)',
            'per_page' => 'Registros por página',
            'page' => 'Página',
        ];

        return view('AutinApi.index', compact(
            'varpantallas',
            'varsubmenus',
            'health',
            'databases',
            'resources',
            'globalResources',
            'defaultDb',
            'apiBaseUrl',
            'proxyBaseUrl',
            'endpoints',
            'gastoRealFilters'
        ));
    }

    public function health(AutinApiClient $api): JsonResponse
    {
        $result = $api->health();

        return response()->json($result['body'] ?? ['message' => $result['message']], $result['ok'] ? 200 : ($result['status'] ?: 502));
    }

    public function cuentasGlobal(Request $request, AutinApiClient $api): JsonResponse
    {
        $result = $api->cuentasGlobal($request->except(['_token']));

        return $this->jsonFromApi($result);
    }

    public function centrosCostoGlobal(Request $request, AutinApiClient $api): JsonResponse
    {
        $result = $api->centrosCostoGlobal($request->except(['_token']));

        return $this->jsonFromApi($result);
    }

    public function gastoReal(Request $request, AutinApiClient $api): JsonResponse
    {
        $result = $api->gastoReal($request->except(['_token']));

        return $this->jsonFromApi($result);
    }

    public function catalogos(Request $request, AutinApiClient $api, string $database): JsonResponse
    {
        try {
            $result = $api->catalogos($database);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->jsonFromApi($result);
    }

    public function resource(Request $request, AutinApiClient $api, string $database, string $resource): JsonResponse
    {
        try {
            $filters = $request->except(['_token']);
            $result = $api->index($resource, $filters, $database);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->jsonFromApi($result);
    }

    public function show(Request $request, AutinApiClient $api, string $database, string $resource, string $id): JsonResponse
    {
        try {
            $filters = $request->except(['_token']);
            $result = $api->show($resource, $id, $filters, $database);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->jsonFromApi($result);
    }

    /**
     * @param  array{ok: bool, status: int, body: array|null, message: string|null}  $result
     */
    protected function jsonFromApi(array $result): JsonResponse
    {
        if ($result['ok']) {
            return response()->json($result['body'] ?? [], 200);
        }

        return response()->json(
            $result['body'] ?? ['message' => $result['message'] ?? 'Error al consultar AutinApi'],
            $result['status'] > 0 ? $result['status'] : 502
        );
    }
}
