<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Conservar la carga más reciente por pedido si hubiera duplicados.
        $duplicados = DB::table('tbl_cargas')
            ->select('pedido_id', DB::raw('COUNT(*) as total'))
            ->groupBy('pedido_id')
            ->having('total', '>', 1)
            ->pluck('pedido_id');

        foreach ($duplicados as $pedidoId) {
            $ids = DB::table('tbl_cargas')
                ->where('pedido_id', $pedidoId)
                ->orderByDesc('id')
                ->pluck('id');
            $mantener = (int) $ids->first();
            $borrar = $ids->filter(fn ($id) => (int) $id !== $mantener)->values()->all();
            if ($borrar !== []) {
                DB::table('tbl_cargas')->whereIn('id', $borrar)->delete();
            }
        }

        $yaUnique = collect(DB::select("SHOW INDEX FROM tbl_cargas WHERE Key_name = 'uk_carga_pedido'"))->isNotEmpty();
        if (!$yaUnique) {
            Schema::table('tbl_cargas', function (Blueprint $table) {
                $table->unique('pedido_id', 'uk_carga_pedido');
            });
        }
    }

    public function down(): void
    {
        $yaUnique = collect(DB::select("SHOW INDEX FROM tbl_cargas WHERE Key_name = 'uk_carga_pedido'"))->isNotEmpty();
        if ($yaUnique) {
            Schema::table('tbl_cargas', function (Blueprint $table) {
                $table->dropUnique('uk_carga_pedido');
            });
        }
    }
};
