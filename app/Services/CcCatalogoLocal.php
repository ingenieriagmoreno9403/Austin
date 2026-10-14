<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Copia local del catálogo SAP (centros / cuentas).
 * No escribe ni lee presupuestos capturados.
 */
class CcCatalogoLocal
{
    public function disponible(): bool
    {
        return Schema::hasTable('tbl_cc_catalogo_centros')
            && Schema::hasTable('tbl_cc_catalogo_cuentas');
    }

    public function tieneDatos(?string $empresa = null): bool
    {
        if (! $this->disponible()) {
            return false;
        }
        $q = DB::table('tbl_cc_catalogo_centros');
        if ($empresa) {
            $q->whereRaw('UPPER(empresa) = ?', [strtoupper($empresa)]);
        }

        return $q->exists();
    }

    /**
     * @return array{centros: array<int, array<string, mixed>>, cuentas: array<int, array<string, mixed>>, empresas: array<int, string>, synced_at: string|null, fuente: string}
     */
    public function leer(?string $empresa = null): array
    {
        if (! $this->disponible()) {
            return [
                'centros' => [],
                'cuentas' => [],
                'empresas' => [],
                'synced_at' => null,
                'fuente' => 'local',
            ];
        }

        $emp = $empresa ? strtoupper(trim($empresa)) : '';

        $centrosQ = DB::table('tbl_cc_catalogo_centros')->orderBy('empresa')->orderBy('codigo');
        $cuentasQ = DB::table('tbl_cc_catalogo_cuentas')->orderBy('empresa')->orderBy('codigo');
        if ($emp !== '') {
            $centrosQ->whereRaw('UPPER(empresa) = ?', [$emp]);
            $cuentasQ->whereRaw('UPPER(empresa) = ?', [$emp]);
        }

        $centros = [];
        foreach ($centrosQ->get() as $row) {
            $centros[] = [
                'codigo' => (string) $row->codigo,
                'nombre' => (string) ($row->nombre ?? ''),
                'empresa' => (string) $row->empresa,
                'activo' => (bool) $row->activo,
                'departamento' => (string) ($row->departamento ?? ''),
            ];
        }

        $cuentas = [];
        foreach ($cuentasQ->get() as $row) {
            $cuentas[] = [
                'codigo' => (string) $row->codigo,
                'nombre' => (string) ($row->nombre ?? ''),
                'empresa' => (string) $row->empresa,
                'grupo' => (string) ($row->grupo ?? ''),
                'grupo_id' => (string) ($row->grupo_id ?? ''),
            ];
        }

        $empresas = collect(array_merge(
            array_column($centros, 'empresa'),
            array_column($cuentas, 'empresa')
        ))->filter()->map(function ($e) {
            return strtoupper((string) $e);
        })->unique()->values()->all();

        return [
            'centros' => $centros,
            'cuentas' => $cuentas,
            'empresas' => $empresas,
            'synced_at' => $this->ultimaSync($emp !== '' ? $emp : null),
            'fuente' => 'local',
        ];
    }

    public function ultimaSync(?string $empresa = null): ?string
    {
        if (! Schema::hasTable('tbl_cc_catalogo_sync')) {
            $q = DB::table('tbl_cc_catalogo_centros')->whereNotNull('synced_at');
            if ($empresa) {
                $q->whereRaw('UPPER(empresa) = ?', [strtoupper($empresa)]);
            }
            $at = $q->max('synced_at');

            return $at ? (string) $at : null;
        }

        $q = DB::table('tbl_cc_catalogo_sync')->where('ok', 1)->whereNotNull('synced_at');
        if ($empresa) {
            $q->whereRaw('UPPER(empresa) = ?', [strtoupper($empresa)]);
        }
        $at = $q->max('synced_at');

        return $at ? (string) $at : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $centros
     * @param  array<int, array<string, mixed>>  $cuentas
     * @return array{centros: int, cuentas: int}
     */
    public function reemplazarEmpresa(string $empresa, array $centros, array $cuentas, ?int $userId = null): array
    {
        $empresa = strtoupper(trim($empresa));
        if ($empresa === '' || ! $this->disponible()) {
            return ['centros' => 0, 'cuentas' => 0];
        }

        $now = now();
        $nCentros = 0;
        $nCuentas = 0;

        DB::transaction(function () use ($empresa, $centros, $cuentas, $userId, $now, &$nCentros, &$nCuentas) {
            // Solo catálogo: no borra presupuestos ni asignaciones.
            DB::table('tbl_cc_catalogo_centros')->whereRaw('UPPER(empresa) = ?', [$empresa])->delete();
            DB::table('tbl_cc_catalogo_cuentas')->whereRaw('UPPER(empresa) = ?', [$empresa])->delete();

            $chunkCentros = [];
            foreach ($centros as $c) {
                $codigo = trim((string) ($c['codigo'] ?? ''));
                if ($codigo === '') {
                    continue;
                }
                $chunkCentros[] = [
                    'empresa' => $empresa,
                    'codigo' => $codigo,
                    'nombre' => mb_substr(trim((string) ($c['nombre'] ?? '')), 0, 180) ?: null,
                    'departamento' => mb_substr(trim((string) ($c['departamento'] ?? '')), 0, 80) ?: null,
                    'activo' => ! empty($c['activo']) ? 1 : 0,
                    'origen' => 'sap',
                    'synced_at' => $now,
                    'synced_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (count($chunkCentros) >= 200) {
                    DB::table('tbl_cc_catalogo_centros')->insert($chunkCentros);
                    $nCentros += count($chunkCentros);
                    $chunkCentros = [];
                }
            }
            if ($chunkCentros) {
                DB::table('tbl_cc_catalogo_centros')->insert($chunkCentros);
                $nCentros += count($chunkCentros);
            }

            $chunkCuentas = [];
            $seen = [];
            foreach ($cuentas as $c) {
                $codigo = trim((string) ($c['codigo'] ?? ''));
                if ($codigo === '') {
                    continue;
                }
                $key = $empresa.'|'.$codigo;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $chunkCuentas[] = [
                    'empresa' => $empresa,
                    'codigo' => $codigo,
                    'nombre' => mb_substr(trim((string) ($c['nombre'] ?? '')), 0, 180) ?: null,
                    'grupo' => mb_substr(trim((string) ($c['grupo'] ?? '')), 0, 80) ?: null,
                    'grupo_id' => mb_substr(trim((string) ($c['grupo_id'] ?? '')), 0, 40) ?: null,
                    'origen' => 'sap',
                    'synced_at' => $now,
                    'synced_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (count($chunkCuentas) >= 200) {
                    DB::table('tbl_cc_catalogo_cuentas')->insert($chunkCuentas);
                    $nCuentas += count($chunkCuentas);
                    $chunkCuentas = [];
                }
            }
            if ($chunkCuentas) {
                DB::table('tbl_cc_catalogo_cuentas')->insert($chunkCuentas);
                $nCuentas += count($chunkCuentas);
            }

            if (Schema::hasTable('tbl_cc_catalogo_sync')) {
                DB::table('tbl_cc_catalogo_sync')->updateOrInsert(
                    ['empresa' => $empresa],
                    [
                        'centros' => $nCentros,
                        'cuentas' => $nCuentas,
                        'ok' => 1,
                        'mensaje' => null,
                        'origen' => 'sap',
                        'synced_at' => $now,
                        'synced_by' => $userId,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        });

        return ['centros' => $nCentros, 'cuentas' => $nCuentas];
    }

    public function marcarFallo(string $empresa, string $mensaje, ?int $userId = null): void
    {
        if (! Schema::hasTable('tbl_cc_catalogo_sync')) {
            return;
        }
        $empresa = strtoupper(trim($empresa));
        if ($empresa === '') {
            return;
        }
        try {
            DB::table('tbl_cc_catalogo_sync')->updateOrInsert(
                ['empresa' => $empresa],
                [
                    'ok' => 0,
                    'mensaje' => mb_substr($mensaje, 0, 255),
                    'origen' => 'sap',
                    'synced_by' => $userId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        } catch (Throwable $e) {
            // silencioso
        }
    }
}
