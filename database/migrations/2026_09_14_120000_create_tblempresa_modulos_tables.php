<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tblempresa_vistas')) {
            Schema::create('tblempresa_vistas', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('id_empresa');
                $table->unsignedInteger('id_vista');
                $table->string('created_by', 100)->nullable();
                $table->timestamps();

                $table->unique(['id_empresa', 'id_vista'], 'emp_vista_unica');
                $table->index('id_empresa', 'emp_vista_empresa');
            });
        }

        if (!Schema::hasTable('tblempresa_perfiles')) {
            Schema::create('tblempresa_perfiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('id_empresa');
                $table->unsignedInteger('id_perfil');
                $table->string('created_by', 100)->nullable();
                $table->timestamps();

                $table->unique(['id_empresa', 'id_perfil'], 'emp_perfil_unica');
                $table->index('id_empresa', 'emp_perfil_empresa');
            });
        }

        if (Schema::hasTable('tblempresas') && !Schema::hasColumn('tblempresas', 'accesos_configurados')) {
            Schema::table('tblempresas', function (Blueprint $table) {
                $table->boolean('accesos_configurados')->default(false);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tblempresa_vistas');
        Schema::dropIfExists('tblempresa_perfiles');

        if (Schema::hasTable('tblempresas') && Schema::hasColumn('tblempresas', 'accesos_configurados')) {
            Schema::table('tblempresas', function (Blueprint $table) {
                $table->dropColumn('accesos_configurados');
            });
        }
    }
};
