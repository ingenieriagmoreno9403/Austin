<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\ClientesAtencion;
use App\Models\Clientes;

class PersonasAtencionEditar extends Component
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
    
    // Propiedades para edición
    public $clientePreSeleccionado;
    public $personaPreSeleccionada;
    public $servicioId;
    
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
    
    public function mount($clienteId = null, $personaId = null, $servicioId = null)
    {
        $this->cargarClientes();
        
        // Almacenar el ID del servicio
        $this->servicioId = $servicioId;
        
        // Si se pasan valores para edición, establecerlos
        if ($clienteId) {
            $this->clientePreSeleccionado = $clienteId;
            $this->clienteId = $clienteId;
            $this->id_cliente = $clienteId;
            $this->cargarPersonasAtencion($clienteId);
            
            // Buscar el cliente para mostrar su nombre
            $cliente = Clientes::find($clienteId);
            if ($cliente) {
                $this->busquedaCliente = $cliente->razon_social . ' (' . $cliente->nombre . ')';
            }
        }
        
        if ($personaId) {
            $this->personaPreSeleccionada = $personaId;
            $this->personaSeleccionada = $personaId;
        }
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
            
            // Actualizar el campo id_atencion en tblservicios_enc
            $this->actualizarIdAtencionEnServicio();
        }
    }
    
    // Método para actualizar id_atencion en la tabla de servicios
    public function actualizarIdAtencionEnServicio()
    {
        try {
            if ($this->servicioId && $this->personaSeleccionada) {
                // Actualizar el servicio en la base de datos
                \DB::table('tblservicios_enc')
                    ->where('id', $this->servicioId)
                    ->update(['id_atencion' => $this->personaSeleccionada]);
                
                // Mostrar mensaje de éxito
                session()->flash('message', 'Persona de atención actualizada correctamente');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar persona de atención: ' . $e->getMessage());
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
    
    // Método para actualizar cliente
    public function actualizarCliente($idCliente)
    {
        if ($idCliente) {
            $this->id_cliente = $idCliente;
            $this->cargarPersonasAtencion($idCliente);
        }
    }
    
    public function render()
    {
        return view('livewire.personas-atencion-editar');
    }
}
