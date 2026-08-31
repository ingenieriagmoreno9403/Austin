<?php
namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Proveedor;
use App\Models\Licitacion;
use Livewire\WithPagination;
use Illuminate\Validation\ValidationException;

class ProveedorSelector extends Component
{
    use WithPagination;

    public $licitacionId;
    public $proveedoresSeleccionados = [];
    public $buscar = '';
    public $sololectura = false;

    protected $listeners = ['guardarTodo' => 'guardar', 'validarProveedores' => 'validarYGuardar'];


    public function updatingBuscar()
    {
        $this->resetPage();
    }

    public function mount($licitacionId = null, $sololectura = false)
    {
        $this->licitacionId = $licitacionId;
        $this->sololectura = $sololectura;

        if ($licitacionId) {
            $this->proveedoresSeleccionados = Licitacion::findOrFail($licitacionId)
                ->proveedores->pluck('id')->toArray();
        }
    }

    public function agregar($id)
    {
        if (!in_array($id, $this->proveedoresSeleccionados)) {
            $this->proveedoresSeleccionados[] = $id;
        }
    }

    public function quitar($id)
    {
        $this->proveedoresSeleccionados = array_diff($this->proveedoresSeleccionados, [$id]);
    }

    public function rules()
    {
        return [
            'proveedoresSeleccionados' => 'required|array|min:1',
        ];
    }

    public function guardar()
    {
        // Emitir evento para iniciar la validación de productos primero
        // El proceso continuará con la validación de proveedores después
        $this->emit('validarProductos');
    }

    /**
     * Validar y guardar los proveedores después de que los productos ya han sido validados
     */
    public function validarYGuardar()
    {
        try {
            $this->validate();

            if ($this->licitacionId) {
                Licitacion::findOrFail($this->licitacionId)
                    ->proveedores()->sync($this->proveedoresSeleccionados);
            }

            // Enviar evento para indicar que todo está listo para enviar el formulario
            $this->dispatchBrowserEvent('proveedores-validos');
        } catch (ValidationException $e) {
            $this->dispatchBrowserEvent('proveedores-no-validos', [
                'mensaje' => 'Debes seleccionar al menos un proveedor.'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.proveedor-selector', [
            'proveedoresDisponibles' => Proveedor::whereNotIn('id', $this->proveedoresSeleccionados)
                ->where('nombre', 'like', '%' . $this->buscar . '%')
                ->orderBy('nombre')
                ->paginate(10),
            'proveedoresSeleccionadosDatos' => Proveedor::whereIn('id', $this->proveedoresSeleccionados)->get(),
        ]);
    }
}
