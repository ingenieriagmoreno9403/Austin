<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Empleados;

class BuscadorEmpleadosEditar extends Component
{
    // Propiedades públicas
    public $empleadoId = '';
    public $busquedaEmpleado = '';
    public $empleadosFiltrados = [];
    public $mostrarResultados = false;
    
    // Propiedad para edición
    public $empleadoPreSeleccionado;
    
    // Método para cargar empleado preseleccionado
    public function cargarEmpleadoPreseleccionado()
    {
        if ($this->empleadoPreSeleccionado && !$this->busquedaEmpleado) {
            $empleado = Empleados::find($this->empleadoPreSeleccionado);
            if ($empleado) {
                $nombreCompleto = trim(
                    $empleado->primer_nombre . ' ' . 
                    ($empleado->segundo_nombre ?: '') . ' ' . 
                    $empleado->apellido_paterno . ' ' . 
                    ($empleado->apellido_materno ?: '')
                );
                $this->busquedaEmpleado = $nombreCompleto;
                $this->empleadoId = $this->empleadoPreSeleccionado;
                \Log::info('BuscadorEmpleadosEditar cargarEmpleadoPreseleccionado - cargado: ' . $nombreCompleto);
            }
        }
    }
    
    public function mount($empleadoId = null)
    {
        // Debug: Log del empleadoId recibido
        \Log::info('BuscadorEmpleadosEditar mount - empleadoId recibido: ' . $empleadoId);
        
        // Si se pasa un ID para edición, establecerlo
        if ($empleadoId) {
            $this->empleadoPreSeleccionado = $empleadoId;
            $this->empleadoId = $empleadoId;
            
            // Buscar el empleado para mostrar su nombre
            $empleado = Empleados::find($empleadoId);
            \Log::info('BuscadorEmpleadosEditar mount - empleado encontrado: ' . ($empleado ? 'Sí' : 'No'));
            
            if ($empleado) {
                $nombreCompleto = trim(
                    $empleado->primer_nombre . ' ' . 
                    ($empleado->segundo_nombre ?: '') . ' ' . 
                    $empleado->apellido_paterno . ' ' . 
                    ($empleado->apellido_materno ?: '')
                );
                $this->busquedaEmpleado = $nombreCompleto;
                \Log::info('BuscadorEmpleadosEditar mount - nombre completo: ' . $nombreCompleto);
            }
        } else {
            \Log::info('BuscadorEmpleadosEditar mount - No se recibió empleadoId');
        }
    }
    
    // Método para buscar empleados
    public function buscarEmpleados()
    {
        if (strlen($this->busquedaEmpleado) >= 2) {
            $this->empleadosFiltrados = Empleados::where('estado', 'A')
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
        
        // Emitir evento para notificar al formulario padre
        $this->dispatchBrowserEvent('empleadoSeleccionado', [
            'empleado_id' => $idEmpleado,
            'empleado_nombre' => $nombreCompleto
        ]);
        
        // Forzar actualización del campo oculto vendedor
        $this->dispatchBrowserEvent('actualizarVendedor', [
            'vendedor_id' => $idEmpleado
        ]);
    }
    
    // Método para limpiar la búsqueda
    public function limpiarBusqueda()
    {
        $this->busquedaEmpleado = '';
        $this->empleadoId = '';
        $this->empleadosFiltrados = [];
        $this->mostrarResultados = false;
        
        // Emitir evento para notificar al formulario padre
        $this->dispatchBrowserEvent('empleadoSeleccionado', [
            'empleado_id' => '',
            'empleado_nombre' => ''
        ]);
        
        // Forzar actualización del campo oculto vendedor
        $this->dispatchBrowserEvent('actualizarVendedor', [
            'vendedor_id' => ''
        ]);
    }
    
    public function render()
    {
        // Debug: Log de los valores actuales antes del render
        \Log::info('BuscadorEmpleadosEditar render - empleadoId: ' . $this->empleadoId . ', busquedaEmpleado: ' . $this->busquedaEmpleado);
        
        return view('livewire.buscador-empleados-editar');
    }
}











