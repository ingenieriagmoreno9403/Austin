<?php

namespace App\Providers;

use App\Models\conceptos_nomina;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Http\Livewire\BuscarProveedor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        require_once app_path('Helpers/helpers.php');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        View::composer(['nominas.*', 'Empleados.*'], function ($view) {
            $view->with('visorFiscalActivo', conceptos_nomina::visorFiscalActivo());
        });

        // Registrar componentes Livewire si es necesario
        if (class_exists('Livewire\Livewire')) {
            Livewire::component('buscar-proveedor', BuscarProveedor::class);
        }
    }
}
