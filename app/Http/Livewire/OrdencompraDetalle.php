<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Producto;
use App\Models\UnidadMedida;

class OrdencompraDetalle extends Component
{
    public $filas = [];
    public $resultados = [];
    public $buscando = [];
    public $totalGeneral = 0;
    public $sololectura = false;

    public function mount($detalle = [])
    {
        $this->filas = $detalle ?: [
            ['producto_id' => '', 'nombre' => '', 'cantidad' => 1, 'umed' =>'', 'observaciones' => '']
        ];
    }

    public function agregarFila()
    {
        $this->filas[] = ['producto_id' => '', 'nombre' => '', 'cantidad' => 1, 'umed' =>'', 'observaciones' => ''];
    }

    public function eliminarFila($index)
    {
        unset($this->filas[$index]);
        $this->filas = array_values($this->filas);
    }

    public function updatedFilas($value, $key)
    {
        if (str_contains($key, '.nombre')) {
            [$filaIndex] = explode('.', str_replace('filas.', '', $key));
            $nombre = $this->filas[$filaIndex]['nombre'];
    
            // Mostrar spinner
            $this->buscando[$filaIndex] = true;
    
            if (strlen($nombre) >= 1) {
                $this->resultados[$filaIndex] = Producto::where('nombre', 'like', '%' . $nombre . '%')
                    ->limit(5)
                    ->get();
            } else {
                $this->resultados[$filaIndex] = [];
            }
    
            // Ocultar spinner
            $this->buscando[$filaIndex] = false;
        }
    }


    public function seleccionarProducto($index, $productoId)
    {
        $producto = Producto::with('unidad')->find($productoId);
    
        if ($producto) {
            $this->filas[$index]['producto_id'] = $producto->id;
            $this->filas[$index]['nombre'] = $producto->nombre;
            $this->filas[$index]['umed'] = $producto->unidad->nombre ?? '';
            unset($this->resultados[$index]); // Oculta sugerencias
        }
    }


    public function limpiarProducto($index)
    {
        $this->filas[$index]['producto_id'] = '';
        $this->filas[$index]['nombre'] = '';
        $this->resultados[$index] = [];
    }

    public function render()
    {
        return view('livewire.ordencompra-detalle');
    }

}
