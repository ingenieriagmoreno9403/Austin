<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Empleados;
use Livewire\WithPagination;

class BuscadorEmpleado extends Component
{
    use WithPagination;

    public $searchEmpleado = '';
    public $empleadoSeleccionado = null;
    public $mostrarResultados = false;

    protected $listeners = ['limpiarSeleccion'];

    public function render()
    {
        $empleados = collect();
        
        if (strlen($this->searchEmpleado) >= 2) {
            $empleados = Empleados::where(function($query) {
                $query->where('primer_nombre', 'like', '%' . $this->searchEmpleado . '%')
                      ->orWhere('segundo_nombre', 'like', '%' . $this->searchEmpleado . '%')
                      ->orWhere('apellido_paterno', 'like', '%' . $this->searchEmpleado . '%')
                      ->orWhere('apellido_materno', 'like', '%' . $this->searchEmpleado . '%')
                      ->orWhere('rfc', 'like', '%' . $this->searchEmpleado . '%');
            })->where('estado', 'A')
              ->limit(10)
              ->get();
        }

        return view('livewire.buscador-empleado', compact('empleados'));
    }

    public function seleccionarEmpleado($empleadoId)
    {
        $this->empleadoSeleccionado = Empleados::find($empleadoId);
        $this->searchEmpleado = $this->empleadoSeleccionado->primer_nombre . ' ' . 
                               ($this->empleadoSeleccionado->segundo_nombre ? $this->empleadoSeleccionado->segundo_nombre . ' ' : '') .
                               $this->empleadoSeleccionado->apellido_paterno . ' ' . 
                               ($this->empleadoSeleccionado->apellido_materno ?? '');
        $this->mostrarResultados = false;
        
        $this->dispatchBrowserEvent('empleadoSeleccionado', [
            'empleado_id' => $empleadoId,
            'empleado_nombre' => $this->empleadoSeleccionado->primer_nombre . ' ' . 
                               ($this->empleadoSeleccionado->segundo_nombre ? $this->empleadoSeleccionado->segundo_nombre . ' ' : '') .
                               $this->empleadoSeleccionado->apellido_paterno . ' ' . 
                               ($this->empleadoSeleccionado->apellido_materno ?? '')
        ]);
    }

    public function limpiarSeleccion()
    {
        $this->searchEmpleado = '';
        $this->empleadoSeleccionado = null;
        $this->mostrarResultados = false;
    }

    public function mostrarResultados()
    {
        if (strlen($this->searchEmpleado) >= 2) {
            $this->mostrarResultados = true;
        } else {
            $this->mostrarResultados = false;
        }
    }

    public function updatedSearchEmpleado()
    {
        $this->mostrarResultados();
    }
}
