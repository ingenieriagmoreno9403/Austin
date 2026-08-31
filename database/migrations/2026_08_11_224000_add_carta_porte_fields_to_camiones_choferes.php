<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCartaPorteFieldsToCamionesChoferes extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tbl_camiones')) {
            Schema::table('tbl_camiones', function (Blueprint $table) {
                if (! Schema::hasColumn('tbl_camiones', 'perm_sct')) {
                    $table->string('perm_sct', 20)->nullable()->after('capacidad_metros');
                }
                if (! Schema::hasColumn('tbl_camiones', 'num_permiso_sct')) {
                    $table->string('num_permiso_sct', 80)->nullable()->after('perm_sct');
                }
                if (! Schema::hasColumn('tbl_camiones', 'config_vehicular')) {
                    $table->string('config_vehicular', 20)->nullable()->after('num_permiso_sct');
                }
                if (! Schema::hasColumn('tbl_camiones', 'anio_modelo')) {
                    $table->string('anio_modelo', 4)->nullable()->after('config_vehicular');
                }
                if (! Schema::hasColumn('tbl_camiones', 'asegura_resp_civil')) {
                    $table->string('asegura_resp_civil', 120)->nullable()->after('anio_modelo');
                }
                if (! Schema::hasColumn('tbl_camiones', 'poliza_resp_civil')) {
                    $table->string('poliza_resp_civil', 80)->nullable()->after('asegura_resp_civil');
                }
            });
        }

        if (Schema::hasTable('tbl_choferes') && ! Schema::hasColumn('tbl_choferes', 'rfc')) {
            Schema::table('tbl_choferes', function (Blueprint $table) {
                $table->string('rfc', 13)->nullable()->after('licencia');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('tbl_camiones')) {
            Schema::table('tbl_camiones', function (Blueprint $table) {
                foreach ([
                    'perm_sct',
                    'num_permiso_sct',
                    'config_vehicular',
                    'anio_modelo',
                    'asegura_resp_civil',
                    'poliza_resp_civil',
                ] as $col) {
                    if (Schema::hasColumn('tbl_camiones', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('tbl_choferes') && Schema::hasColumn('tbl_choferes', 'rfc')) {
            Schema::table('tbl_choferes', function (Blueprint $table) {
                $table->dropColumn('rfc');
            });
        }
    }
}
