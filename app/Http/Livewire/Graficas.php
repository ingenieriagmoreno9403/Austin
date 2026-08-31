<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Traits\GraficasTraits;
use Log;

class Graficas extends Component
{
    //use GraficasTraits;

    public $sucursales = [];

    public $selectedSucursal = null;

    public $charts = [];


    public $grafica2info = [];


    public function mount()
    {

         //Configuracion grafica2
         $this->sucursales = $this->getSucursales();
         $this->grafica2info = $this->total_canjesXultimos4meses();
         $labels = [];
         $saldos = [];
         $total_prestamos = [];
         foreach($this->grafica2info as $key => $value){
             $this->grafica2info[$key]->mes = ucfirst($value->mes);
                $labels[] = $value->mes;
                $saldos[] = $value->saldo;
                $total_prestamos[] = $value->total_prestamos;
             
         }
         // Crear gráfico
         $this->charts = [
             $this->chart2($labels, $saldos, $total_prestamos),
         ];
    }
    public function render()
    {
        return view('livewire.graficas', [
            'charts' => $this->charts,
            'sucursales' => $this->sucursales,
            'grafica2info' => $this->grafica2info
        ]);
    }

    public function  updatedSelectedSucursal($value)
    {

        $this->sucursales = $this->getSucursales();
        $this->grafica2info = $this->total_canjesXultimos4meses($value);
         $labels = [];
         $saldos = [];
         $total_prestamos = [];


         foreach($this->grafica2info as $key => $value){
             $this->grafica2info[$key]->mes = ucfirst($value->mes);
                $labels[] = $value->mes;
                $saldos[] = $value->saldo;
                $total_prestamos[] = $value->total_prestamos;
             
         }
         // Crear gráficos
         $this->charts = [
             $this->chart2($labels, $saldos, $total_prestamos),
         ];
         $this->emit('chartDataUpdated', $this->charts[0]);


        
    }

    public function chart2($labels, $saldos, $total_prestamos)
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Monto total vales',
                        'backgroundColor' => '#2E90A4',
                        'borderColor' => '#000000',
                        'borderWidth' => 0.3,
                        'data' => $saldos
                    ],
                    [
                        'label' => 'Canjes',
                        'backgroundColor' => '#FFB74D',
                        'borderColor' => '#000000',
                        'borderWidth' => 0.3,
                        'data' => $total_prestamos
                    ],
                ]
            ],
            'options' => [
                'maintainAspectRatio' => false,
                'scales' => [
                    'x' => [
                        'stacked' => true,
                    ],
                    'y' => [
                        'stacked' => true,
                        'beginAtZero' => true,
                    ]
                ],
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom'
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Número de canjes'
                    ],
                ]
            ],

        ];
    }
    
}
