<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alta de UoM CFDI/SAP faltantes para Captura (H87=Pieza, XBX=Caja, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tblunidadesmedida')) {
            return;
        }

        $units = [
            ['code' => 'XBX', 'nombre' => 'CAJA', 'sat' => 'XBX'],
            ['code' => 'RK', 'nombre' => 'ROLLO', 'sat' => 'RK'],
            ['code' => 'XSA', 'nombre' => 'SACO', 'sat' => 'XSA'],
            ['code' => 'H87', 'nombre' => 'PIEZA', 'sat' => 'H87'],
            ['code' => 'E48', 'nombre' => 'UNIDAD DE SERVICIO', 'sat' => 'E48'],
            ['code' => 'KGM', 'nombre' => 'KILOGRAMO', 'sat' => 'KGM'],
            ['code' => 'MTR', 'nombre' => 'METRO', 'sat' => 'MTR'],
        ];

        $hasAbrev = Schema::hasColumn('tblunidadesmedida', 'abreviacion');
        $hasSat = Schema::hasColumn('tblunidadesmedida', 'c_unidad_medida');
        $hasIdSat = Schema::hasColumn('tblunidadesmedida', 'id_sat');
        $hasDesc = Schema::hasColumn('tblunidadesmedida', 'descripcion');

        foreach ($units as $u) {
            $code = $u['code'];
            $exists = DB::table('tblunidadesmedida')
                ->where(function ($q) use ($code, $hasAbrev, $hasSat) {
                    if ($hasAbrev) {
                        $q->orWhereRaw('UPPER(abreviacion) = ?', [strtoupper($code)]);
                    }
                    if ($hasSat) {
                        $q->orWhereRaw('UPPER(c_unidad_medida) = ?', [strtoupper($code)]);
                    }
                    $q->orWhereRaw('UPPER(nombre) = ?', [strtoupper($code)]);
                })
                ->exists();
            if ($exists) {
                continue;
            }
            $row = ['nombre' => $u['nombre']];
            if ($hasAbrev) {
                $row['abreviacion'] = $code;
            }
            if ($hasSat) {
                $row['c_unidad_medida'] = $u['sat'];
            }
            if ($hasIdSat) {
                $row['id_sat'] = 0;
            }
            if ($hasDesc) {
                $row['descripcion'] = 'Catálogo CFDI / SAP UoM '.$code;
            }
            DB::table('tblunidadesmedida')->insert($row);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tblunidadesmedida')) {
            return;
        }
        $codes = ['XBX', 'RK', 'XSA', 'H87', 'E48', 'KGM', 'MTR'];
        DB::table('tblunidadesmedida')
            ->where(function ($q) use ($codes) {
                $q->whereIn('abreviacion', $codes)
                    ->orWhereIn('c_unidad_medida', $codes);
            })
            ->where('descripcion', 'like', 'Catálogo CFDI / SAP UoM%')
            ->delete();
    }
};
