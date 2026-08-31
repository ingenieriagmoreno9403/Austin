<?php

namespace App\Console\Commands;

use App\Services\VincularCatalogoMaestroTuberiaService;
use Illuminate\Console\Command;

class VincularCatalogoMaestroTuberiaCommand extends Command
{
    protected $signature = 'tubo:vincular-catalogo-maestro
        {archivo? : Ruta del Excel Catalogo_Maestro_Tuberia_HDPE.xlsx}
        {--solo-nuevos : No actualizar especificaciones ya existentes}';

    protected $description = 'Vincula productos de tubería con especificaciones del catálogo maestro HDPE';

    public function handle(VincularCatalogoMaestroTuberiaService $service): int
    {
        $archivo = $this->argument('archivo')
            ?? 'C:\\Users\\gamo9\\Downloads\\IOHISA\\Catalogo_Maestro_Tuberia_HDPE.xlsx';

        $this->info("Vinculando desde: {$archivo}");

        try {
            $resultado = $service->vincular($archivo, ! $this->option('solo-nuevos'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Concepto', 'Cantidad'],
            collect($resultado)->map(fn ($valor, $clave) => [$clave, $valor])->values()->all()
        );

        $this->info('Vinculación completada.');

        return self::SUCCESS;
    }
}
