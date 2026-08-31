<?php

namespace App\Console\Commands;

use App\Services\ImportarTuboEspecificacionesService;
use Illuminate\Console\Command;

class ImportarTuboEspecificacionesCommand extends Command
{
    protected $signature = 'tubo:importar-especificaciones
        {archivo? : Ruta del Excel}
        {--producto= : ID del producto en tblproductos}
        {--material=HDPE PE4710 : Material del tubo}';

    protected $description = 'Importa la tabla de especificaciones de tubo desde el Excel IOHISA';

    public function handle(ImportarTuboEspecificacionesService $service): int
    {
        $archivo = $this->argument('archivo')
            ?? 'C:\\Users\\gamo9\\Downloads\\IOHISA\\tabla iohisa 2025.xlsx';

        try {
            $resultado = $service->importar(
                $archivo,
                $this->option('producto') ? (int) $this->option('producto') : null,
                (string) $this->option('material')
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Importación completada.');
        $this->line('Material: ' . $resultado['material']);
        $this->line('Productos por diámetro: ' . $resultado['productos']);
        $this->line('Productos nuevos: ' . ($resultado['productos_nuevos'] ?? 0));
        $this->line('Especificaciones importadas: ' . $resultado['importados']);

        return self::SUCCESS;
    }
}
