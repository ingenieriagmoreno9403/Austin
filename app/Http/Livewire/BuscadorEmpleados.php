<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Empleados;

class BuscadorEmpleados extends Component
{
    // Propiedades públicas
    public $empleadoId = '';
    public $busquedaEmpleado = '';
    public $empleadosFiltrados = [];
    public $mostrarResultados = false;
    
    public function mount()
    {
        // Inicializar el componente
    }
    
    // Método para buscar empleados
    public function buscarEmpleados()
    {
        if (strlen($this->busquedaEmpleado) >= 2) {
            $this->empleadosFiltrados = empleados::where('estado', 'A')
                ->where(function($query) {
                    $query->where('primer_nombre', 'like', '%' . $this->busquedaEmpleado . '%')
                          ->orWhere('segundo_nombre', 'like', '%' . $this->busquedaEmpleado . '%')
                          ->orWhere('apellido_paterno', 'like', '%' . $this->busquedaEmpleado . '%')
                          ->orWhere('apellido_materno', 'like', '%' . $this->busquedaEmpleado . '%');
                })
                ->limit(10)
                ->get();
            $this->mostrarResultados = true;
        } else {
            $this->empleadosFiltrados = [];
            $this->mostrarResultados = false;
        }
    }
    
    // Método para seleccionar un empleado del buscador
    public function seleccionarEmpleado($idEmpleado, $nombreCompleto)
    {
        $this->empleadoId = $idEmpleado;
        $this->busquedaEmpleado = $nombreCompleto;
        $this->mostrarResultados = false;
        $this->empleadosFiltrados = [];
    }
    
    // Método para limpiar la búsqueda
    public function limpiarBusqueda()
    {
        $this->busquedaEmpleado = '';
        $this->empleadoId = '';
        $this->empleadosFiltrados = [];
        $this->mostrarResultados = false;
    }
    
    public function render()
    {
        return view('livewire.buscador-empleados');
    }
}
