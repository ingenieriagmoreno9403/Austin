<?php 

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Proveedor;
use App\Models\ProveedoresAtencion;
use Illuminate\Support\Facades\Log;

class BuscarProveedor extends Component
{
    public $search = '';
    public $resultados = [];
    public $proveedor_id;
    public $nombre_seleccionado;
    public $sololectura = false;
    public $eslicitacion = false;
    public $mensaje_error = '';
    
    // Nuevas propiedades para personas de atención
    public $personasAtencion = [];
    public $personaAtencionSeleccionada = null;
    public $mostrarNuevaPersona = false;
    public $nuevaPersona = [
        'primer_nombre' => '',
        'segundo_nombre' => '',
        'apellido_paterno' => '',
        'apellido_materno' => '',
        'puesto' => ''
    ];
    public $formularioCompleto = false;

    protected $listeners = ['validarFormulario' => 'validarFormulario'];

    public function mount($proveedor_id = null, $proveedor = null, $sololectura = false, $eslicitacion = false, $persona_atencion_id = null, $nombre_seleccionado = null)
    {
        $this->sololectura = $sololectura;
        $this->eslicitacion = $eslicitacion;
        
        Log::info("Mount BuscarProveedor: proveedor_id={$proveedor_id}, proveedor={$proveedor}, persona_atencion_id={$persona_atencion_id}, nombre_seleccionado={$nombre_seleccionado}");
        
        // Usar proveedor_id si se pasa, o proveedor como respaldo
        $proveedorId = $proveedor_id ?? $proveedor;
        
        // Si se pasa un proveedor, lo seleccionamos
        if (!empty($proveedorId)) {
            try {
                $prov = Proveedor::find($proveedorId);
                if ($prov) {
                    $this->proveedor_id = $prov->id;
                    $this->nombre_seleccionado = $nombre_seleccionado ?? $prov->nombre;
                    $this->search = $this->nombre_seleccionado;
                    
                    Log::info("Proveedor encontrado: {$prov->id} - {$prov->nombre}");
                    
                    // Cargar personas de atención
                    $this->cargarPersonasAtencion();
                    
                    // Si se pasa un persona_atencion_id, lo seleccionamos
                    if (!empty($persona_atencion_id)) {
                        $this->personaAtencionSeleccionada = intval($persona_atencion_id);
                        
                        Log::info("Intentando seleccionar persona: {$this->personaAtencionSeleccionada}");
                        
                        // Verificar que la persona existe en la lista cargada
                        $personaExiste = $this->personasAtencion->contains('id', $this->personaAtencionSeleccionada);
                        if (!$personaExiste) {
                            Log::info("Persona no encontrada en lista, buscando específicamente...");
                            // Si no existe en la lista actual, buscar la persona específica
                            $personaEspecifica = ProveedoresAtencion::where('id', $this->personaAtencionSeleccionada)
                                ->where('id_proveedor', $this->proveedor_id)
                                ->first();
                            
                            if ($personaEspecifica) {
                                Log::info("Persona específica encontrada: {$personaEspecifica->primer_nombre}");
                                // Agregar la persona a la lista si existe
                                $this->personasAtencion->push($personaEspecifica);
                            } else {
                                Log::info("Persona específica NO encontrada, limpiando selección");
                                // Si no existe, limpiar la selección
                                $this->personaAtencionSeleccionada = null;
                            }
                        } else {
                            Log::info("Persona encontrada en la lista cargada");
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error al cargar proveedor: ' . $e->getMessage());
                $this->mensaje_error = 'Error al cargar el proveedor.';
            }
        }
    }

    public function updatedSearch()
    {
        $this->mensaje_error = '';
        $this->resultados = [];
        
        if (strlen($this->search) >= 2) {
            try {
                $this->resultados = Proveedor::where('nombre', 'like', '%' . $this->search . '%')
                                          ->orWhere('id', 'like', '%' . $this->search . '%')
                                          ->limit(10)
                                          ->get();
                                          
                if (count($this->resultados) == 0) {
                    $this->mensaje_error = 'No se encontraron proveedores con ese nombre o ID.';
                }
            } catch (\Exception $e) {
                Log::error('Error en búsqueda de proveedor: ' . $e->getMessage());
                $this->mensaje_error = 'Error al buscar proveedores: ' . $e->getMessage();
            }
        }
    }

    public function seleccionarProveedor($id)
    {
        try {
            $proveedor = Proveedor::find($id);

            if ($proveedor) {
                $this->proveedor_id = $proveedor->id;
                $this->nombre_seleccionado = $proveedor->nombre;
                $this->search = $proveedor->nombre;
                $this->resultados = [];
                $this->mensaje_error = '';
                $this->personaAtencionSeleccionada = null;
                $this->mostrarNuevaPersona = false;
                $this->limpiarNuevaPersona();
                $this->cargarPersonasAtencion();
            } else {
                $this->mensaje_error = 'No se pudo encontrar el proveedor seleccionado.';
            }
        } catch (\Exception $e) {
            Log::error('Error al seleccionar proveedor: ' . $e->getMessage());
            $this->mensaje_error = 'Error al seleccionar el proveedor.';
        }
    }
    
    public function cargarPersonasAtencion()
    {
        if ($this->proveedor_id) {
            try {
                $this->personasAtencion = ProveedoresAtencion::where('id_proveedor', $this->proveedor_id)
                    ->get();
                
                Log::info("Cargando personas para proveedor {$this->proveedor_id}: encontradas " . $this->personasAtencion->count());
                    
                // Si hay una persona seleccionada, verificar que existe en la lista
                if ($this->personaAtencionSeleccionada) {
                    $personaExiste = $this->personasAtencion->contains('id', $this->personaAtencionSeleccionada);
                    Log::info("Verificando persona {$this->personaAtencionSeleccionada}: existe = " . ($personaExiste ? 'SI' : 'NO'));
                    if (!$personaExiste) {
                        // Si la persona no existe en la lista, limpiar la selección
                        $this->personaAtencionSeleccionada = null;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error al cargar personas de atención: ' . $e->getMessage());
                $this->personasAtencion = [];
            }
        } else {
            $this->personasAtencion = [];
        }
    }
    
    public function verificarPersonaAtencionExistente()
    {
        if ($this->personaAtencionSeleccionada && $this->proveedor_id) {
            try {
                $persona = ProveedoresAtencion::where('id', $this->personaAtencionSeleccionada)
                    ->where('id_proveedor', $this->proveedor_id)
                    ->first();
                    
                if (!$persona) {
                    $this->personaAtencionSeleccionada = null;
                }
            } catch (\Exception $e) {
                Log::error('Error al verificar persona de atención: ' . $e->getMessage());
                $this->personaAtencionSeleccionada = null;
            }
        }
    }
    
    public function seleccionarPersonaAtencion($id)
    {
        $this->personaAtencionSeleccionada = $id;
        $this->mostrarNuevaPersona = false;
        
        // Emitir evento para notificar que se seleccionó una persona
        $this->emit('personaSeleccionada', $id);
    }
    
    public function cambiarPersonaAtencion()
    {
        Log::info("cambiarPersonaAtencion llamado");
        
        // Limpiar la selección actual
        $this->personaAtencionSeleccionada = null;
        
        // Ocultar el formulario de nueva persona para mostrar la lista de selección
        $this->mostrarNuevaPersona = false;
        
        Log::info("Mostrando lista de selección de personas");
        
        // Emitir evento para notificar que se deseleccionó la persona
        $this->emit('personaDeseleccionada');
    }
    
    public function toggleNuevaPersona()
    {
        Log::info("toggleNuevaPersona llamado. Estado actual: " . ($this->mostrarNuevaPersona ? 'true' : 'false'));
        
        // Si ya está mostrando el formulario, lo ocultamos
        if ($this->mostrarNuevaPersona) {
            $this->mostrarNuevaPersona = false;
            Log::info("Formulario de nueva persona desactivado");
        } else {
            // Si no está mostrando el formulario, lo activamos
            $this->mostrarNuevaPersona = true;
            $this->personaAtencionSeleccionada = null;
            Log::info("Formulario de nueva persona activado");
        }
        
        Log::info("Nuevo estado: " . ($this->mostrarNuevaPersona ? 'true' : 'false'));
    }
    

    
    public function limpiarNuevaPersona()
    {
        $this->nuevaPersona = [
            'primer_nombre' => '',
            'segundo_nombre' => '',
            'apellido_paterno' => '',
            'apellido_materno' => '',
            'puesto' => ''
        ];
    }
    
    public function guardarNuevaPersona()
    {
        $this->validate([
            'nuevaPersona.primer_nombre' => 'required|min:2|max:50',
            'nuevaPersona.apellido_paterno' => 'required|min:2|max:50',
            'nuevaPersona.puesto' => 'required|min:2|max:100'
        ]);
        
        try {
            $nuevaPersona = ProveedoresAtencion::create([
                'id_proveedor' => $this->proveedor_id,
                'primer_nombre' => $this->nuevaPersona['primer_nombre'],
                'segundo_nombre' => $this->nuevaPersona['segundo_nombre'],
                'apellido_paterno' => $this->nuevaPersona['apellido_paterno'],
                'apellido_materno' => $this->nuevaPersona['apellido_materno'],
                'puesto' => $this->nuevaPersona['puesto']
            ]);
            
            // Seleccionar automáticamente la nueva persona
            $this->personaAtencionSeleccionada = $nuevaPersona->id;
            $this->mostrarNuevaPersona = false;
            $this->limpiarNuevaPersona();
            $this->cargarPersonasAtencion();
            
            session()->flash('message', 'Persona de atención agregada correctamente y seleccionada.');
            
            // Emitir evento para notificar que se seleccionó una persona
            $this->emit('personaSeleccionada', $nuevaPersona->id);
            
        } catch (\Exception $e) {
            Log::error('Error al guardar persona de atención: ' . $e->getMessage());
            session()->flash('error', 'Error al guardar la persona de atención: ' . $e->getMessage());
        }
    }

    public function limpiarProveedor()
    {
        $this->proveedor_id = null;
        $this->nombre_seleccionado = null;
        $this->search = '';
        $this->resultados = [];
        $this->mensaje_error = '';
        $this->personasAtencion = [];
        $this->personaAtencionSeleccionada = null;
        $this->mostrarNuevaPersona = false;
        $this->limpiarNuevaPersona();
    }
    
    /**
     * Validar que el formulario esté completo antes de enviar
     */
    public function validarFormulario()
    {
        $errores = [];
        
        // Validar que se haya seleccionado un proveedor
        if (empty($this->proveedor_id)) {
            $errores[] = 'Debe seleccionar un proveedor.';
        }
        
        // Validar que se haya seleccionado una persona de atención
        if (empty($this->personaAtencionSeleccionada)) {
            $errores[] = 'Debe seleccionar una persona de atención.';
        }
        
        // Si hay errores, emitir evento con los errores
        if (!empty($errores)) {
            $this->emit('formularioIncompleto', $errores);
            return false;
        }
        
        // Si todo está bien, emitir evento de formulario válido
        $this->emit('formularioValido');
        return true;
    }
    
    /**
     * Actualizar el estado del formulario
     */
    public function actualizarEstadoFormulario()
    {
        $this->formularioCompleto = !empty($this->proveedor_id) && !empty($this->personaAtencionSeleccionada);
    }

    public function render()
    {
        // Verificar que la persona de atención existente sea válida
        $this->verificarPersonaAtencionExistente();
        
        // Actualizar el estado del formulario
        $this->actualizarEstadoFormulario();
        
        return view('livewire.buscar-proveedor');
    }
}
