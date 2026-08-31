<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\ClientesAtencion;
use App\Models\Clientes;

class PersonasAtencion extends Component
{
    // Propiedades públicas para el formulario
    public $id_cliente;
    public $personasAtencion = [];
    public $personaSeleccionada;
    
    // Propiedades para crear nueva persona
    public $primer_nombre;
    public $segundo_nombre;
    public $apellido_paterno;
    public $apellido_materno;
    public $telefono;
    public $correo;
    
    // Propiedades para el modal
    public $mostrarModal = false;
    
    // Propiedades para el cliente
    public $clienteSeleccionado;
    public $clientesActivos = [];
    public $clienteId = '';
    public $busquedaCliente = '';
    public $clientesFiltrados = [];
    public $mostrarResultados = false;
    
    protected $rules = [
        'primer_nombre' => 'required|min:2',
        'apellido_paterno' => 'required|min:2',
        'telefono' => 'nullable|max:10',
        'correo' => 'nullable|email'
    ];
    
    protected $messages = [
        'primer_nombre.required' => 'El primer nombre es obligatorio',
        'primer_nombre.min' => 'El primer nombre debe tener al menos 2 caracteres',
        'apellido_paterno.required' => 'El apellido paterno es obligatorio',
        'apellido_paterno.min' => 'El apellido paterno debe tener al menos 2 caracteres',
        'telefono.max' => 'El teléfono no puede tener más de 10 dígitos',
        'correo.email' => 'El correo electrónico no es válido'
    ];
    
    public function mount()
    {
        $this->cargarClientes();
    }
    
    public function cargarClientes()
    {
        $this->clientesActivos = Clientes::where('estado', 'A')->get();
    }
    
    public function cargarPersonasAtencion($idCliente)
    {
        if ($idCliente) {
            $this->id_cliente = $idCliente;
            $this->personasAtencion = ClientesAtencion::where('id_cliente', $idCliente)->get();
        } else {
            $this->personasAtencion = [];
        }
    }
    
    public function abrirModal()
    {
        $this->resetFormulario();
        $this->mostrarModal = true;
    }
    
    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetFormulario();
    }
    
    public function resetFormulario()
    {
        $this->primer_nombre = '';
        $this->segundo_nombre = '';
        $this->apellido_paterno = '';
        $this->apellido_materno = '';
        $this->telefono = '';
        $this->correo = '';
    }
    
    public function guardarPersona()
    {
        $this->validate();
        
        try {
            $personaAtencion = new ClientesAtencion();
            $personaAtencion->id_cliente = $this->id_cliente;
            $personaAtencion->primer_nombre = strtoupper($this->primer_nombre);
            $personaAtencion->segundo_nombre = $this->segundo_nombre ? strtoupper($this->segundo_nombre) : null;
            $personaAtencion->apellido_paterno = strtoupper($this->apellido_paterno);
            $personaAtencion->apellido_materno = $this->apellido_materno ? strtoupper($this->apellido_materno) : null;
            $personaAtencion->telefono = $this->telefono ? strtoupper($this->telefono) : null;
            $personaAtencion->correo = $this->correo ? strtoupper($this->correo) : null;
            $personaAtencion->save();
            
            // Recargar personas de atención
            $this->cargarPersonasAtencion($this->id_cliente);
            
            // Cerrar modal
            $this->cerrarModal();
            
            // Mostrar mensaje de éxito
            session()->flash('message', 'Persona de atención creada correctamente');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear la persona de atención: ' . $e->getMessage());
        }
    }
    
    public function seleccionarPersona($idPersona)
    {
        $this->personaSeleccionada = $idPersona;
        $this->emit('personaSeleccionada', $idPersona);
    }
    
    // Método para manejar cambios en la selección de persona
    public function updatedPersonaSeleccionada()
    {
        if ($this->personaSeleccionada) {
            $this->emit('personaSeleccionada', $this->personaSeleccionada);
        }
    }
    
    // Método para escuchar cambios en el select de cliente del formulario principal
    public function actualizarCliente($idCliente)
    {
        // Debug: verificar que se reciba el ID
        \Log::info('PersonasAtencion: actualizarCliente llamado con ID: ' . $idCliente);
        
        if ($idCliente) {
            $this->id_cliente = $idCliente;
            $this->cargarPersonasAtencion($idCliente);
            
            // Debug: verificar que se carguen las personas
            \Log::info('PersonasAtencion: personas cargadas: ' . $this->personasAtencion->count());
        }
    }
    
    // Método para escuchar eventos de Livewire
    protected $listeners = ['clienteSeleccionado' => 'actualizarCliente'];
    
    // Método público para ser llamado desde JavaScript
    public function setCliente($idCliente)
    {
        \Log::info('PersonasAtencion: setCliente llamado con ID: ' . $idCliente);
        $this->actualizarCliente($idCliente);
    }
    
    // Método alternativo usando wire:click
    public function seleccionarCliente($idCliente)
    {
        \Log::info('PersonasAtencion: seleccionarCliente llamado con ID: ' . $idCliente);
        $this->actualizarCliente($idCliente);
    }
    
    // Método para manejar cambios en el select de cliente
    public function updatedClienteId()
    {
        \Log::info('PersonasAtencion: clienteId actualizado a: ' . $this->clienteId);
        if ($this->clienteId) {
            $this->actualizarCliente($this->clienteId);
        }
    }
    
    // Método para buscar clientes
    public function buscarClientes()
    {
        if (strlen($this->busquedaCliente) >= 2) {
            $this->clientesFiltrados = Clientes::where('estado', 'A')
                ->where(function($query) {
                    $query->where('razon_social', 'like', '%' . $this->busquedaCliente . '%')
                          ->orWhere('nombre', 'like', '%' . $this->busquedaCliente . '%');
                })
                ->limit(10)
                ->get();
            $this->mostrarResultados = true;
        } else {
            $this->clientesFiltrados = [];
            $this->mostrarResultados = false;
        }
    }
    
    // Método para seleccionar un cliente del buscador
    public function seleccionarClienteBuscador($idCliente, $razonSocial, $nombre)
    {
        $this->clienteId = $idCliente;
        $this->busquedaCliente = $razonSocial . ' (' . $nombre . ')';
        $this->mostrarResultados = false;
        $this->clientesFiltrados = [];
        
        // Actualizar el cliente seleccionado
        $this->actualizarCliente($idCliente);
    }
    
    // Método para limpiar la búsqueda
    public function limpiarBusqueda()
    {
        $this->busquedaCliente = '';
        $this->clienteId = '';
        $this->clientesFiltrados = [];
        $this->mostrarResultados = false;
        $this->personasAtencion = [];
        $this->id_cliente = null;
    }
    
    public function render()
    {
        return view('livewire.personas-atencion');
    }
}
