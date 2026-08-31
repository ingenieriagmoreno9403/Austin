<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_ordenes')) {
            return;
        }

        Schema::table('tbl_ordenes', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_ordenes', 'ubicacion_destino_id')) {
                $table->unsignedInteger('ubicacion_destino_id')->nullable()->after('nave_destino');
            }
        });

        if (Schema::hasColumn('tbl_ordenes', 'nave_destino')) {
            DB::statement('ALTER TABLE tbl_ordenes MODIFY COLUMN nave_destino VARCHAR(150) NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_ordenes')) {
            return;
        }

        Schema::table('tbl_ordenes', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_ordenes', 'ubicacion_destino_id')) {
                $table->dropColumn('ubicacion_destino_id');
            }
        });
    }
};
