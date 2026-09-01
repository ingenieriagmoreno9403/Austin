<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPrincipalToCcAsignaciones extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tbl_cc_asignaciones')) {
            return;
        }

        Schema::table('tbl_cc_asignaciones', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_cc_asignaciones', 'es_principal')) {
                $table->boolean('es_principal')->default(true)->after('centro_nombre');
            }
            if (! Schema::hasColumn('tbl_cc_asignaciones', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('es_principal');
                $table->index('parent_id', 'cc_asig_parent');
            }
        });

        $grupos = DB::table('tbl_cc_asignaciones')
            ->select('ciclo_codigo', 'empresa', 'centro_codigo')
            ->groupBy('ciclo_codigo', 'empresa', 'centro_codigo')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($grupos as $g) {
            $ids = DB::table('tbl_cc_asignaciones')
                ->where('ciclo_codigo', $g->ciclo_codigo)
                ->where('empresa', $g->empresa)
                ->where('centro_codigo', $g->centro_codigo)
                ->orderBy('id')
                ->pluck('id');
            $principalId = $ids->first();
            $otros = $ids->slice(1)->values()->all();
            if ($otros) {
                DB::table('tbl_cc_asignaciones')
                    ->whereIn('id', $otros)
                    ->update(['es_principal' => 0, 'parent_id' => $principalId]);
            }
        }
    }

    public function down()
    {
        if (! Schema::hasTable('tbl_cc_asignaciones')) {
            return;
        }
        Schema::table('tbl_cc_asignaciones', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_cc_asignaciones', 'parent_id')) {
                $table->dropIndex('cc_asig_parent');
                $table->dropColumn('parent_id');
            }
            if (Schema::hasColumn('tbl_cc_asignaciones', 'es_principal')) {
                $table->dropColumn('es_principal');
            }
        });
    }
}
