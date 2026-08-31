<?php

namespace App\Console\Commands;

use App\Services\ImportarProductosSatService;
use Illuminate\Console\Command;

class ImportarProductosSatCommand extends Command
{
    protected $signature = 'productos:importar-sat
        {archivo? : Ruta del Excel PRODUCTOS_SAT_CATEGORIAS_UNIDADES_MYSQL.xlsx}
        {--solo-nuevos : No actualizar productos que ya existan por SKU}';

    protected $description = 'Importa productos desde el Excel con categorías, unidades y clave SAT';

    public function handle(ImportarProductosSatService $service): int
    {
        $archivo = $this->argument('archivo')
            ?? 'C:\\Users\\gamo9\\Downloads\\IOHISA\\PRODUCTOS_SAT_CATEGORIAS_UNIDADES_MYSQL.xlsx';

        if (! is_readable($archivo)) {
            $alterno = storage_path('app/PRODUCTOS_SAT_CATEGORIAS_UNIDADES_MYSQL.xlsx');
            if (is_readable($alterno)) {
                $archivo = $alterno;
            }
        }

        $this->info("Importando desde: {$archivo}");

        try {
            $resultado = $service->importar($archivo, ! $this->option('solo-nuevos'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Concepto', 'Cantidad'],
            collect($resultado)->map(fn ($valor, $clave) => [$clave, $valor])->values()->all()
        );

        $this->info('Importación finalizada.');

        return self::SUCCESS;
    }
}
