<?php

namespace App\Console\Commands;

use App\Services\ImportarTuboEspecificacionesService;
use Illuminate\Console\Command;

class VincularProductosTuboCommand extends Command
{
    protected $signature = 'tubo:vincular-productos
        {--material=HDPE PE4710 : Material del tubo}';

    protected $description = 'Crea un producto por diámetro y vincula las especificaciones existentes';

    public function handle(ImportarTuboEspecificacionesService $service): int
    {
        try {
            $resultado = $service->vincularProductosPorDiametro((string) $this->option('material'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Productos vinculados correctamente.');
        $this->line('Productos por diámetro: ' . $resultado['productos']);
        $this->line('Productos nuevos: ' . ($resultado['productos_nuevos'] ?? 0));
        $this->line('Especificaciones vinculadas: ' . $resultado['importados']);

        return self::SUCCESS;
    }
}
