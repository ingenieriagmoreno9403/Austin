<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_prov_doc_tipos')) {
            Schema::create('tbl_prov_doc_tipos', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nombre', 150);
                $table->string('clave', 60)->nullable();
                $table->boolean('obligatorio')->default(true);
                $table->boolean('es_contrato')->default(false);
                $table->boolean('activo')->default(true);
                $table->unsignedSmallInteger('orden')->default(1);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('tblproveedor_documentos')) {
            Schema::table('tblproveedor_documentos', function (Blueprint $table) {
                if (! Schema::hasColumn('tblproveedor_documentos', 'tipo_id')) {
                    $table->unsignedInteger('tipo_id')->nullable()->after('proveedor_id');
                    $table->index('tipo_id');
                }
                if (! Schema::hasColumn('tblproveedor_documentos', 'uploaded_by')) {
                    $table->unsignedBigInteger('uploaded_by')->nullable()->after('archivo');
                }
                if (! Schema::hasColumn('tblproveedor_documentos', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });
        }

        $this->seedTipos();
    }

    protected function seedTipos(): void
    {
        if (DB::table('tbl_prov_doc_tipos')->exists()) {
            return;
        }

        $now = now();
        $tipos = [
            ['nombre' => 'Contrato', 'clave' => 'contrato', 'obligatorio' => 1, 'es_contrato' => 1, 'orden' => 1],
            ['nombre' => 'Constancia de situación fiscal', 'clave' => 'csf', 'obligatorio' => 1, 'es_contrato' => 0, 'orden' => 2],
            ['nombre' => 'Opinión de cumplimiento', 'clave' => 'opinion', 'obligatorio' => 1, 'es_contrato' => 0, 'orden' => 3],
            ['nombre' => 'Identificación oficial', 'clave' => 'identificacion', 'obligatorio' => 0, 'es_contrato' => 0, 'orden' => 4],
            ['nombre' => 'Comprobante de domicilio', 'clave' => 'domicilio', 'obligatorio' => 0, 'es_contrato' => 0, 'orden' => 5],
        ];

        foreach ($tipos as $t) {
            DB::table('tbl_prov_doc_tipos')->insert([
                'nombre' => $t['nombre'],
                'clave' => $t['clave'],
                'obligatorio' => $t['obligatorio'],
                'es_contrato' => $t['es_contrato'],
                'activo' => 1,
                'orden' => $t['orden'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tblproveedor_documentos')) {
            Schema::table('tblproveedor_documentos', function (Blueprint $table) {
                if (Schema::hasColumn('tblproveedor_documentos', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
                if (Schema::hasColumn('tblproveedor_documentos', 'uploaded_by')) {
                    $table->dropColumn('uploaded_by');
                }
                if (Schema::hasColumn('tblproveedor_documentos', 'tipo_id')) {
                    $table->dropColumn('tipo_id');
                }
            });
        }

        Schema::dropIfExists('tbl_prov_doc_tipos');
    }
};
