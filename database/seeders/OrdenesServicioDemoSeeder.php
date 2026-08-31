<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class OrdenesServicioDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tipos = DB::table('tbltipos_vehiculos')->where('estado', 'A')->pluck('tipo_vehiculo')->toArray();
        $torres = DB::table('tbltorres_taller')->where('estado', 'A')->pluck('codigo_torre')->toArray();
        $servicios = DB::table('tblservicios_taller_catalogo')->where('estado', 'A')->pluck('nombre_servicio')->toArray();

        if (empty($tipos) || empty($torres) || empty($servicios)) {
            $this->command->warn('No hay catalogos suficientes para sembrar ordenes demo.');
            return;
        }

        $procesos = [
            'PROCESO NORMAL',
            'PENDIENTE X AUTORIZAR',
            'PENDIENTE X REFACCIONES',
            'X REFACCIONES, YA SURTIDO',
            'PENDIENTE (OTRO)',
            'DOBLE TRABAJO ASIGNADO',
        ];

        $insertadas = 0;
        DB::beginTransaction();
        try {
            for ($i = 1; $i <= 25; $i++) {
                $noOrden = 'DMO' . str_pad((string) $i, 5, '0', STR_PAD_LEFT);

                if (DB::table('tblordenes_servicio_enc')->where('no_orden', $noOrden)->exists()) {
                    continue;
                }

                $fechaHora = Carbon::now()->subDays(rand(0, 12))->setTime(rand(8, 18), [0, 15, 30, 45][array_rand([0, 1, 2, 3])]);
                $fechaPromesa = (clone $fechaHora)->addDays(rand(1, 6));

                $idEnc = DB::table('tblordenes_servicio_enc')->insertGetId([
                    'no_orden' => $noOrden,
                    'tipo_vehiculo' => $tipos[array_rand($tipos)],
                    'codigo_torre' => $torres[array_rand($torres)],
                    'estado' => ['En espera', 'En proceso', 'Proceso'][array_rand([0, 1, 2])],
                    'fecha_hora' => $fechaHora,
                    'fecha_promesa' => $fechaPromesa,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => 'seed_demo',
                    'updated_by' => 'seed_demo',
                ]);

                $totalServicios = rand(2, 5);
                for ($j = 1; $j <= $totalServicios; $j++) {
                    $proceso = $j === 1 ? 'PROCESO NORMAL' : $procesos[array_rand($procesos)];
                    if ($j === $totalServicios && rand(0, 3) === 0) {
                        $proceso = 'PROCESO A PUNTO DE ENTREGAR';
                    }

                    DB::table('tblordenes_servicio_det')->insert([
                        'id_orden_enc' => $idEnc,
                        'no_s' => $j,
                        'servicio' => $servicios[array_rand($servicios)],
                        'descripcion' => 'SERVICIO DEMO ' . $j . ' DE ORDEN ' . $noOrden,
                        't_tab' => (float) (rand(1, 9) . '.' . rand(0, 9) . rand(0, 9)),
                        't_real' => (float) (rand(1, 9) . '.' . rand(0, 9) . rand(0, 9)),
                        'proceso_estado' => $proceso,
                        'id_empleado_asignado' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => 'seed_demo',
                        'updated_by' => 'seed_demo',
                    ]);
                }

                $insertadas++;
            }

            DB::commit();
            $this->command->info('Ordenes demo insertadas: ' . $insertadas);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error al insertar demo: ' . $e->getMessage());
        }
    }
}

