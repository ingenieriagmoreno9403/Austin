<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Clientes;
use App\Models\ClientesAtencion;
use Livewire\WithPagination;

class BuscadorCliente extends Component
{
    use WithPagination;

    public $searchCliente = '';
    public $searchPersona = '';
    public $clienteSeleccionado = null;
    public $personaSeleccionada = null;
    public $personasAtencion = [];
    public $mostrarPersonas = false;
    public $nuevaPersona = [
        'primer_nombre' => '',
        'segundo_nombre' => '',
        'apellido_paterno' => '',
        'apellido_materno' => '',
        'telefono' => '',
        'correo' => ''
    ];
    public $mostrarFormNuevaPersona = false;

    protected $listeners = [];
    
    public function mount()
    {
        // En Livewire v2, usamos listeners en el mount
        $this->listeners = ['limpiarSeleccion'];
    }

    public function render()
    {
        $clientes = collect();
        
        if (strlen($this->searchCliente) >= 2) {
            $clientes = Clientes::where(function($query) {
                $query->where('nombre', 'like', '%' . $this->searchCliente . '%')
                      ->orWhere('razon_social', 'like', '%' . $this->searchCliente . '%')
                      ->orWhere('alias', 'like', '%' . $this->searchCliente . '%');
            })
              ->limit(10)
              ->get();
        }

        return view('livewire.buscador-cliente', compact('clientes'));
    }

    public function seleccionarCliente($clienteId)
    {
        $this->clienteSeleccionado = Clientes::find($clienteId);
        $this->searchCliente = $this->clienteSeleccionado->razon_social;
        $this->cargarPersonasAtencion($clienteId);
        $this->mostrarPersonas = true;
        $this->dispatchBrowserEvent('clienteSeleccionado', [
            'cliente_id' => $clienteId,
            'cliente_nombre' => $this->clienteSeleccionado->razon_social
        ]);
    }

    public function cargarPersonasAtencion($clienteId)
    {
        $this->personasAtencion = ClientesAtencion::where('id_cliente', $clienteId)
            ->get();
    }

    public function seleccionarPersona($personaId)
    {
        $this->personaSeleccionada = ClientesAtencion::find($personaId);
        $this->searchPersona = $this->personaSeleccionada->nombre_completo;
        $this->dispatchBrowserEvent('personaSeleccionada', [
            'persona_id' => $personaId,
            'persona_nombre' => $this->personaSeleccionada->nombre_completo
        ]);
    }

    public function mostrarFormularioNuevaPersona()
    {
        $this->mostrarFormNuevaPersona = true;
        $this->resetearFormularioNuevaPersona();
    }

    public function resetearFormularioNuevaPersona()
    {
        $this->nuevaPersona = [
            'primer_nombre' => '',
            'segundo_nombre' => '',
            'apellido_paterno' => '',
            'apellido_materno' => '',
            'telefono' => '',
            'correo' => ''
        ];
    }

    public function guardarNuevaPersona()
    {
        $this->validate([
            'nuevaPersona.primer_nombre' => 'required|min:2',
            'nuevaPersona.apellido_paterno' => 'required|min:2',
            'nuevaPersona.telefono' => 'nullable|min:10',
            'nuevaPersona.correo' => 'nullable|email'
        ]);

        try {
            $persona = new ClientesAtencion();
            $persona->id_cliente = $this->clienteSeleccionado->id;
            $persona->primer_nombre = $this->nuevaPersona['primer_nombre'];
            $persona->segundo_nombre = $this->nuevaPersona['segundo_nombre'];
            $persona->apellido_paterno = $this->nuevaPersona['apellido_paterno'];
            $persona->apellido_materno = $this->nuevaPersona['apellido_materno'];
            $persona->telefono = $this->nuevaPersona['telefono'];
            $persona->correo = $this->nuevaPersona['correo'];
            $persona->save();

            // Recargar personas de atención
            $this->cargarPersonasAtencion($this->clienteSeleccionado->id);
            
            // Seleccionar la nueva persona
            $this->seleccionarPersona($persona->id);
            
            // Ocultar formulario
            $this->mostrarFormNuevaPersona = false;
            
            $this->dispatchBrowserEvent('personaCreada', [
                'message' => 'Persona de atención creada correctamente'
            ]);

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'Error al crear la persona de atención: ' . $e->getMessage()
            ]);
        }
    }

    public function limpiarSeleccion()
    {
        $this->clienteSeleccionado = null;
        $this->personaSeleccionada = null;
        $this->searchCliente = '';
        $this->searchPersona = '';
        $this->personasAtencion = [];
        $this->mostrarPersonas = false;
        $this->mostrarFormNuevaPersona = false;
        $this->resetearFormularioNuevaPersona();
    }

    public function limpiarCliente()
    {
        $this->clienteSeleccionado = null;
        $this->searchCliente = '';
        $this->personasAtencion = [];
        $this->mostrarPersonas = false;
        $this->personaSeleccionada = null;
        $this->searchPersona = '';
        $this->mostrarFormNuevaPersona = false;
    }

    public function limpiarPersona()
    {
        $this->personaSeleccionada = null;
        $this->searchPersona = '';
    }
}
