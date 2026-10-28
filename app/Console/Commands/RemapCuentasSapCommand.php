<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RemapCuentasSapCommand extends Command
{
    protected $signature = 'cc:remap-cuentas-sap
        {--dry-run : Solo cuenta, no escribe}
        {--indemnizaciones : Sustituye el par 879/945 por las dos cuentas SAP}';

    protected $description = 'Pasa consecutivos viejos al FormatCode SAP si el nombre es único o único de gasto 6000. Con --indemnizaciones reemplaza 879/945 por el par SAP.';

    /** @var array<string, array<string, array<int, string>>> */
    protected $catalogo = [];

    public function handle(): int
    {
        if (! Schema::hasTable('tbl_cc_catalogo_cuentas')) {
            $this->error('Falta tbl_cc_catalogo_cuentas.');

            return self::FAILURE;
        }

        $this->cargarCatalogo();
        $dry = (bool) $this->option('dry-run');
        if ($dry) {
            $this->warn('Dry-run: no se escribe nada.');
        }

        $asig = $this->remapAsignaciones($dry);
        $ppto = $this->remapPresupuestos($dry);
        $grupos = $this->remapGrupos($dry);

        $this->table(
            ['Tabla', 'Actualizados', 'Fusionados (ya existía SAP)', 'Sin cambio'],
            [
                ['asignaciones', $asig['updated'], $asig['merged'], $asig['skipped']],
                ['presupuestos', $ppto['updated'], $ppto['merged'], $ppto['skipped']],
                ['grupos', $grupos['updated'], $grupos['merged'], $grupos['skipped']],
            ]
        );

        if ($this->option('indemnizaciones')) {
            $ind = $this->reemplazarParIndemnizaciones($dry);
            $this->table(
                ['Indemnizaciones', 'Asignaciones tocadas', 'Grupos tocados', 'Cortos borrados'],
                [['879/945 → par SAP', $ind['asignaciones'], $ind['grupos'], $ind['borrados']]]
            );
        }

        return self::SUCCESS;
    }

    protected function cargarCatalogo(): void
    {
        foreach (DB::table('tbl_cc_catalogo_cuentas')->get(['empresa', 'codigo', 'nombre']) as $row) {
            $emp = $this->empresaCanon((string) $row->empresa);
            $norm = $this->normalizarNombre((string) ($row->nombre ?? ''));
            $codigo = trim((string) $row->codigo);
            if ($emp === '' || $norm === '' || $codigo === '') {
                continue;
            }
            if (! isset($this->catalogo[$emp][$norm])) {
                $this->catalogo[$emp][$norm] = [];
            }
            if (! in_array($codigo, $this->catalogo[$emp][$norm], true)) {
                $this->catalogo[$emp][$norm][] = $codigo;
            }
        }
    }

    protected function codigoSap(string $empresa, string $codigo, string $nombre): string
    {
        $codigo = trim($codigo);
        if ($codigo === '' || ! $this->esCodigoViejo($codigo)) {
            return '';
        }
        $norm = $this->normalizarNombre($nombre);
        if ($norm === '') {
            return '';
        }
        $hits = $this->catalogo[$this->empresaCanon($empresa)][$norm] ?? [];

        return $this->elegirUnico($hits);
    }

    /**
     * @param  array<int, string>  $hits
     */
    protected function elegirUnico(array $hits): string
    {
        $hits = array_values(array_unique(array_filter(array_map('trim', $hits))));
        if (count($hits) === 1) {
            return $hits[0];
        }
        $gastos = array_values(array_filter($hits, function ($codigo) {
            return strpos($codigo, '6000') === 0;
        }));

        return count($gastos) === 1 ? $gastos[0] : '';
    }

    /**
     * @return array{updated: int, merged: int, skipped: int}
     */
    protected function remapAsignaciones(bool $dry): array
    {
        $out = ['updated' => 0, 'merged' => 0, 'skipped' => 0];
        if (! Schema::hasTable('tbl_cc_asignacion_cuentas')) {
            return $out;
        }

        $rows = DB::table('tbl_cc_asignacion_cuentas as c')
            ->join('tbl_cc_asignaciones as a', 'a.id', '=', 'c.asignacion_id')
            ->select(['c.id', 'c.asignacion_id', 'c.cuenta_codigo', 'c.cuenta_nombre', 'a.empresa'])
            ->orderBy('c.id')
            ->get();

        foreach ($rows as $row) {
            $sap = $this->codigoSap((string) $row->empresa, (string) $row->cuenta_codigo, (string) ($row->cuenta_nombre ?? ''));
            if ($sap === '' || $sap === trim((string) $row->cuenta_codigo)) {
                if ($this->esCodigoViejo((string) $row->cuenta_codigo)) {
                    $out['skipped']++;
                }
                continue;
            }
            $existe = DB::table('tbl_cc_asignacion_cuentas')
                ->where('asignacion_id', $row->asignacion_id)
                ->where('cuenta_codigo', $sap)
                ->where('id', '!=', $row->id)
                ->exists();
            if ($dry) {
                $existe ? $out['merged']++ : $out['updated']++;
                continue;
            }
            try {
                if ($existe) {
                    DB::table('tbl_cc_asignacion_cuentas')->where('id', $row->id)->delete();
                    $out['merged']++;
                } else {
                    DB::table('tbl_cc_asignacion_cuentas')->where('id', $row->id)->update([
                        'cuenta_codigo' => $sap,
                        'updated_at' => now(),
                    ]);
                    $out['updated']++;
                }
            } catch (Throwable $e) {
                $this->warn('Asignación '.$row->id.': '.$e->getMessage());
                $out['skipped']++;
            }
        }

        return $out;
    }

    /**
     * @return array{updated: int, merged: int, skipped: int}
     */
    protected function remapPresupuestos(bool $dry): array
    {
        $out = ['updated' => 0, 'merged' => 0, 'skipped' => 0];
        if (! Schema::hasTable('tbl_cc_presupuestos')) {
            return $out;
        }

        $meses = [];
        for ($i = 1; $i <= 12; $i++) {
            $meses[] = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        }

        $cols = array_merge(
            ['id', 'ciclo_codigo', 'empresa', 'centro_codigo', 'cuenta_codigo', 'cuenta_nombre'],
            $meses
        );
        $rows = DB::table('tbl_cc_presupuestos')
            ->orderBy('id')
            ->get($cols);

        foreach ($rows as $row) {
            $sap = $this->codigoSap((string) $row->empresa, (string) $row->cuenta_codigo, (string) ($row->cuenta_nombre ?? ''));
            if ($sap === '' || $sap === trim((string) $row->cuenta_codigo)) {
                if ($this->esCodigoViejo((string) $row->cuenta_codigo)) {
                    $out['skipped']++;
                }
                continue;
            }
            $otro = DB::table('tbl_cc_presupuestos')
                ->where('ciclo_codigo', $row->ciclo_codigo)
                ->where('empresa', $row->empresa)
                ->where('centro_codigo', $row->centro_codigo)
                ->where('cuenta_codigo', $sap)
                ->where('id', '!=', $row->id)
                ->first();
            if ($dry) {
                $otro ? $out['merged']++ : $out['updated']++;
                continue;
            }
            try {
                if ($otro) {
                    $patch = [];
                    foreach ($meses as $col) {
                        if ($otro->{$col} === null && $row->{$col} !== null) {
                            $patch[$col] = $row->{$col};
                        }
                    }
                    if ($patch !== []) {
                        $patch['updated_at'] = now();
                        DB::table('tbl_cc_presupuestos')->where('id', $otro->id)->update($patch);
                    }
                    DB::table('tbl_cc_presupuestos')->where('id', $row->id)->delete();
                    $out['merged']++;
                } else {
                    DB::table('tbl_cc_presupuestos')->where('id', $row->id)->update([
                        'cuenta_codigo' => $sap,
                        'updated_at' => now(),
                    ]);
                    $out['updated']++;
                }
            } catch (Throwable $e) {
                $this->warn('Presupuesto '.$row->id.': '.$e->getMessage());
                $out['skipped']++;
            }
        }

        return $out;
    }

    /**
     * @return array{updated: int, merged: int, skipped: int}
     */
    protected function remapGrupos(bool $dry): array
    {
        $out = ['updated' => 0, 'merged' => 0, 'skipped' => 0];
        if (! Schema::hasTable('tbl_cc_grupo_cuentas') || ! Schema::hasTable('tbl_cc_grupos_cuenta')) {
            return $out;
        }

        $rows = DB::table('tbl_cc_grupo_cuentas as i')
            ->join('tbl_cc_grupos_cuenta as g', 'g.id', '=', 'i.grupo_id')
            ->select(['i.id', 'i.grupo_id', 'i.cuenta_codigo', 'i.cuenta_nombre', 'g.empresa'])
            ->orderBy('i.id')
            ->get();

        foreach ($rows as $row) {
            $sap = $this->codigoSap((string) $row->empresa, (string) $row->cuenta_codigo, (string) ($row->cuenta_nombre ?? ''));
            if ($sap === '' || $sap === trim((string) $row->cuenta_codigo)) {
                if ($this->esCodigoViejo((string) $row->cuenta_codigo)) {
                    $out['skipped']++;
                }
                continue;
            }
            $existe = DB::table('tbl_cc_grupo_cuentas')
                ->where('grupo_id', $row->grupo_id)
                ->where('cuenta_codigo', $sap)
                ->where('id', '!=', $row->id)
                ->exists();
            if ($dry) {
                $existe ? $out['merged']++ : $out['updated']++;
                continue;
            }
            try {
                if ($existe) {
                    DB::table('tbl_cc_grupo_cuentas')->where('id', $row->id)->delete();
                    $out['merged']++;
                } else {
                    DB::table('tbl_cc_grupo_cuentas')->where('id', $row->id)->update([
                        'cuenta_codigo' => $sap,
                        'updated_at' => now(),
                    ]);
                    $out['updated']++;
                }
            } catch (Throwable $e) {
                $this->warn('Grupo item '.$row->id.': '.$e->getMessage());
                $out['skipped']++;
            }
        }

        return $out;
    }

    /**
     * 879 y 945 son el mismo par de gasto; no hay presupuesto en esos códigos.
     * Se deja el par SAP y se quitan los consecutivos.
     *
     * @return array{asignaciones: int, grupos: int, borrados: int}
     */
    protected function reemplazarParIndemnizaciones(bool $dry): array
    {
        $viejos = ['879', '945'];
        $sap = ['600060001013', '600060001024'];
        $out = ['asignaciones' => 0, 'grupos' => 0, 'borrados' => 0];
        $now = now();

        $asigIds = DB::table('tbl_cc_asignacion_cuentas')
            ->whereIn('cuenta_codigo', $viejos)
            ->distinct()
            ->pluck('asignacion_id');
        $out['asignaciones'] = $asigIds->count();

        foreach ($asigIds as $asigId) {
            foreach ($sap as $codigo) {
                $existe = DB::table('tbl_cc_asignacion_cuentas')
                    ->where('asignacion_id', $asigId)
                    ->where('cuenta_codigo', $codigo)
                    ->exists();
                if ($existe || $dry) {
                    continue;
                }
                DB::table('tbl_cc_asignacion_cuentas')->insert([
                    'asignacion_id' => $asigId,
                    'cuenta_codigo' => $codigo,
                    'cuenta_nombre' => 'INDEMNIZACIONES',
                    'agrupacion' => 'Gastos',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $grupoIds = Schema::hasTable('tbl_cc_grupo_cuentas')
            ? DB::table('tbl_cc_grupo_cuentas')->whereIn('cuenta_codigo', $viejos)->distinct()->pluck('grupo_id')
            : collect();
        $out['grupos'] = $grupoIds->count();

        foreach ($grupoIds as $grupoId) {
            foreach ($sap as $codigo) {
                $existe = DB::table('tbl_cc_grupo_cuentas')
                    ->where('grupo_id', $grupoId)
                    ->where('cuenta_codigo', $codigo)
                    ->exists();
                if ($existe || $dry) {
                    continue;
                }
                DB::table('tbl_cc_grupo_cuentas')->insert([
                    'grupo_id' => $grupoId,
                    'cuenta_codigo' => $codigo,
                    'cuenta_nombre' => 'INDEMNIZACIONES',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $qAsig = DB::table('tbl_cc_asignacion_cuentas')->whereIn('cuenta_codigo', $viejos);
        $qGrupo = Schema::hasTable('tbl_cc_grupo_cuentas')
            ? DB::table('tbl_cc_grupo_cuentas')->whereIn('cuenta_codigo', $viejos)
            : null;
        $out['borrados'] = $qAsig->count() + ($qGrupo ? $qGrupo->count() : 0);
        if (! $dry) {
            $qAsig->delete();
            if ($qGrupo) {
                $qGrupo->delete();
            }
        }

        return $out;
    }

    protected function esCodigoViejo(string $codigo): bool
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return false;
        }
        if (preg_match('/SYS/i', $codigo)) {
            return true;
        }
        $digits = preg_replace('/\D+/', '', $codigo) ?? '';

        return $digits !== '' && $digits === $codigo && strlen($digits) < 8;
    }

    protected function empresaCanon(string $empresa): string
    {
        $emp = strtoupper(trim($empresa));

        return $emp === 'ABSA' ? 'AUSTIN' : $emp;
    }

    protected function normalizarNombre(string $nombre): string
    {
        $s = trim($nombre);
        if ($s === '') {
            return '';
        }
        if (class_exists(\Normalizer::class)) {
            $n = \Normalizer::normalize($s, \Normalizer::FORM_D);
            if (is_string($n) && $n !== '') {
                $s = $n;
            }
        }
        $s = preg_replace('/\p{Mn}+/u', '', $s) ?? $s;
        $s = mb_strtoupper($s);
        $s = str_replace('Ñ', 'N', $s);
        $s = preg_replace('/[^A-Z0-9]+/', ' ', $s) ?? $s;

        return trim(preg_replace('/\s+/', ' ', $s) ?? $s);
    }
}
