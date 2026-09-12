<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddImportarPermisoCc extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tbl_cc_tipos_permiso')) {
            $exists = DB::table('tbl_cc_tipos_permiso')->where('clave', 'importar')->exists();
            if (! $exists) {
                $now = now();
                DB::table('tbl_cc_tipos_permiso')->insert([
                    'clave' => 'importar',
                    'nombre' => 'Importar masivo',
                    'descripcion' => 'Puede descargar plantilla e importar presupuestos de todos sus centros. Aplica al usuario en todo el ciclo.',
                    'orden' => 4,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! Schema::hasTable('tbl_cc_usuario_permisos')) {
            Schema::create('tbl_cc_usuario_permisos', function (Blueprint $table) {
                $table->id();
                $table->string('ciclo_codigo', 40);
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('permiso_id');
                $table->timestamps();

                $table->unique(['ciclo_codigo', 'user_id', 'permiso_id'], 'cc_user_perm_unica');
                $table->index(['ciclo_codigo', 'user_id'], 'cc_user_perm_ciclo_user');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_cc_usuario_permisos');
        if (Schema::hasTable('tbl_cc_tipos_permiso')) {
            DB::table('tbl_cc_tipos_permiso')->where('clave', 'importar')->delete();
        }
    }
}
