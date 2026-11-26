<?php
namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Proveedor;
use App\Models\Licitacion;
use App\Support\HomologacionProveedor;
use App\Services\ProveedorDocumentacionService;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Illuminate\Validation\ValidationException;

class ProveedorSelector extends Component
{
    use WithPagination;

    public $licitacionId;
    public $proveedoresSeleccionados = [];
    public $buscar = '';
    public $categoriaId = '';
    public $sololectura = false;

    protected $listeners = ['guardarTodo' => 'guardar', 'validarProveedores' => 'validarYGuardar'];

    protected function docsService(): ProveedorDocumentacionService
    {
        return app(ProveedorDocumentacionService::class);
    }


    public function updatingBuscar()
    {
        $this->resetPage();
    }

    public function updatingCategoriaId()
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
        $proveedor = Proveedor::find($id);
        if (! $proveedor || ! HomologacionProveedor::puedeInvitar($proveedor->homologacion ?? null)) {
            $this->dispatchBrowserEvent('proveedores-no-validos', [
                'mensaje' => 'Ese proveedor no se puede invitar por su homologación.'
            ]);
            return;
        }

        $docEstado = $this->docsService()->estado((int) $id);
        if (! $docEstado['puede_invitar']) {
            $extra = $docEstado['faltantes'] ? (' Faltan: '.implode(', ', $docEstado['faltantes']).'.') : '';
            if (in_array($docEstado['contrato_estado'], ['vencido', 'por_vencer', 'sin_contrato'], true)) {
                $extra .= ' Contrato: '.$docEstado['etiqueta'].'.';
            }
            $this->dispatchBrowserEvent('proveedores-no-validos', [
                'mensaje' => 'No se puede invitar a '.$proveedor->nombre.': documentación incompleta o contrato no vigente.'.$extra
            ]);
            return;
        }

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
            $yaInvitados = [];
            if ($this->licitacionId) {
                $yaInvitados = DB::table('tbllicitacion_proveedor')
                    ->where('licitacion_id', $this->licitacionId)
                    ->pluck('proveedor_id')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->all();
            }

            $ids = array_map('intval', $this->proveedoresSeleccionados);
            $catalogo = Proveedor::whereIn('id', $ids)->get()->keyBy('id');
            $validos = [];
            $rechazados = [];
            $rechazadosDocs = [];
            foreach ($ids as $id) {
                // Ya invitados se conservan (aunque ahora fallen docs: se gestionan fuera)
                if (in_array($id, $yaInvitados, true)) {
                    $validos[] = $id;
                    continue;
                }

                $proveedor = $catalogo->get($id);
                if (! $proveedor) {
                    continue;
                }

                if (! HomologacionProveedor::puedeInvitar($proveedor->homologacion ?? null)) {
                    $rechazados[] = $proveedor->nombre;
                    continue;
                }

                if (! $this->docsService()->estado($id)['puede_invitar']) {
                    $rechazadosDocs[] = $proveedor->nombre;
                    continue;
                }

                $validos[] = $id;
            }

            $this->proveedoresSeleccionados = $validos;
            if ($rechazados || $rechazadosDocs) {
                $msg = [];
                if ($rechazados) {
                    $msg[] = 'Homologación: '.implode(', ', $rechazados);
                }
                if ($rechazadosDocs) {
                    $msg[] = 'Documentos/contrato: '.implode(', ', $rechazadosDocs);
                }
                $this->dispatchBrowserEvent('proveedores-no-validos', [
                    'mensaje' => 'No se invitaron — '.implode(' · ', $msg).'.'
                ]);
                return;
            }

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
        $categorias = DB::table('tblcategoria_proveedor')->orderBy('nombre')->get();
        $nombres = $categorias->pluck('nombre', 'id');

        $disponibles = Proveedor::whereNotIn('id', $this->proveedoresSeleccionados)
            ->where('nombre', 'like', '%' . $this->buscar . '%')
            ->where(function ($q) {
                $q->whereIn('homologacion', ['aprobado', 'condicionado'])
                    ->orWhereNull('homologacion')
                    ->orWhere('homologacion', '');
            });

        if ($this->categoriaId !== '' && (int) $this->categoriaId > 0) {
            $disponibles->where('categoria_id', (int) $this->categoriaId);
        }

        $preciosAplicados = collect();
        if ($this->licitacionId && count($this->proveedoresSeleccionados)) {
            $preciosAplicados = DB::table('tbllicitacionproducto_proveedor')
                ->where('licitacion_id', $this->licitacionId)
                ->whereIn('proveedor_id', $this->proveedoresSeleccionados)
                ->where('costo', '>', 0)
                ->groupBy('proveedor_id')
                ->select(
                    'proveedor_id',
                    DB::raw('COUNT(*) as lineas'),
                    DB::raw('SUM(CASE WHEN total > 0 THEN total ELSE costo END) as importe')
                )
                ->get()
                ->keyBy('proveedor_id');
        }

        $page = $disponibles
            ->orderByDesc('preferido')
            ->orderBy('nombre')
            ->paginate(10);

        $idsPagina = $page->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $docEstados = $this->docsService()->mapaEstados($idsPagina);

        return view('livewire.proveedor-selector', [
            'proveedoresDisponibles' => $page,
            'proveedoresSeleccionadosDatos' => Proveedor::whereIn('id', $this->proveedoresSeleccionados)->get(),
            'categorias' => $categorias,
            'nombresCategoria' => $nombres,
            'preciosAplicados' => $preciosAplicados,
            'docEstados' => $docEstados,
        ]);
    }
}
