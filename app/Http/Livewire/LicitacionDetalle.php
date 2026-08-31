<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Producto;
use App\Models\UnidadMedida;
class LicitacionDetalle extends Component
{
    public $filas = [];
    public $resultados = [];
    public $buscando = [];
    public $totalGeneral = 0;
    public $sololectura = false;
    public $eslicitacion = true;
    public $errorProducto = null;
    public $hayProductosValidos = false;
    public $filasInvalidas = []; // Nuevo array para almacenar índices de filas inválidas

    protected $listeners = ['guardarTodo' => 'guardar', 'validarProductos' => 'guardar'];

    public function mount($detalle = [])
    {
        $this->filas = $detalle ?: [
            ['producto_id' => '', 'nombre' => '', 'cantidad' => 1, 'umed' =>'', 'observaciones' => '','costo' => 0, 'subtotal' => 0]
        ];

        foreach ($this->filas as $i => $fila) {
            $this->actualizarSubtotal($i);
        }
        
        $this->validarProductos();
    }

    public function agregarFila()
    {
        $this->filas[] = [
            'producto_id' => '', 
            'nombre' => '', 
            'cantidad' => 1, 
            'umed' => '', 
            'observaciones' => '',
            'costo' => 0, 
            'subtotal' => 0
        ];
        
        // Calcular el subtotal de la nueva fila
        $ultimoIndex = count($this->filas) - 1;
        $this->actualizarSubtotal($ultimoIndex);
    }

    public function eliminarFila($index)
    {
        unset($this->filas[$index]);
        $this->filas = array_values($this->filas);
        $this->validarProductos();
    }

    public function updatedFilas($value, $key)  // ocurre cuando se actualiza un campo en filas
    {
        if (str_contains($key, '.nombre')) {
            [$filaIndex] = explode('.', str_replace('filas.', '', $key));
            $nombre = $this->filas[$filaIndex]['nombre'];
    
            // Mostrar spinner
            $this->buscando[$filaIndex] = true;
    
            if (strlen($nombre) >= 1) {
                $this->resultados[$filaIndex] = Producto::where('nombre', 'like', '%' . $nombre . '%')
                    ->limit(10)
                    ->get();
            } else {
                $this->resultados[$filaIndex] = [];
            }
    
            // Ocultar spinner
            $this->buscando[$filaIndex] = false;
        }

        if (str_contains($key, '.cantidad') || str_contains($key, '.costo')) {
            [$filaIndex] = explode('.', str_replace('filas.', '', $key));
            $this->actualizarSubtotal($filaIndex);
        }
    }
    
    public function updatedFilasCantidad($value, $key)
    {
        if (str_contains($key, '.cantidad')) {
            [$filaIndex] = explode('.', str_replace('filas.', '', $key));
            $this->actualizarSubtotal($filaIndex);
        }
    }
    
    public function updatedFilasCosto($value, $key)
    {
        if (str_contains($key, '.costo')) {
            [$filaIndex] = explode('.', str_replace('filas.', '', $key));
            $this->actualizarSubtotal($filaIndex);
        }
    }

    public function seleccionarProducto($index, $productoId)
    {
        // Validar si ya fue seleccionado en otra fila
        foreach ($this->filas as $i => $fila) {
            if ($i != $index && $fila['producto_id'] == $productoId) {
                unset($this->resultados[$index]); 
                $this->dispatchBrowserEvent('producto-duplicado', [
                    'mensaje' => 'Este producto ya fue agregado en otra línea.'
                ]);
                return;
            }
        }

        $producto = Producto::with('unidad')->find($productoId);
    
        if ($producto) {
            $this->filas[$index]['producto_id'] = $producto->id;
            $this->filas[$index]['nombre'] = $producto->nombre;
            $this->filas[$index]['umed'] = $producto->unidad->nombre ?? '';
            unset($this->resultados[$index]); // Oculta sugerencias
            
            // Actualizar el estado de validación
            $this->validarProductos();
        }
    }

    public function actualizarSubtotal($index)
    {
        $fila = $this->filas[$index];
        $cantidad = floatval($fila['cantidad'] ?? 0);
        $costo = floatval($fila['costo'] ?? 0);
        $subtotal = $cantidad * $costo;
        
        // Actualizar el subtotal en la fila
        $this->filas[$index]['subtotal'] = round($subtotal, 2);
        
        // También actualizar el total general
        $this->totalGeneral = $this->calcularTotalGeneral();
    }
    
    public function formatearSubtotal($subtotal)
    {
        return number_format(floatval($subtotal), 2);
    }

    public function calcularTotalGeneral()
    {
        $total = collect($this->filas)
            ->sum(function ($item) {
                return floatval($item['subtotal'] ?? 0);
            });
        
        return round($total, 2);
    }
    
    public function recalcularTodosLosSubtotales()
    {
        foreach ($this->filas as $index => $fila) {
            $this->actualizarSubtotal($index);
        }
    }
    
    public function limpiarProducto($index)
    {
        $this->filas[$index]['producto_id'] = '';
        $this->filas[$index]['nombre'] = '';
        $this->filas[$index]['umed'] = '';
        $this->resultados[$index] = [];
        $this->validarProductos();
    }
    
    /**
     * Valida si hay al menos un producto válido en la lista y que todas las filas tengan un producto_id válido
     */
    private function validarProductos()
    {
        $this->filasInvalidas = [];
        $filasValidas = 0;
        $todasValidas = true;
        
        // Verificar cada fila
        foreach ($this->filas as $index => $fila) {
            // Una fila es válida si tiene producto_id y nombre
            $esValida = !empty($fila['producto_id']) && !empty($fila['nombre']);
            
            if ($esValida) {
                $filasValidas++;
            } else {
                // Si hay contenido en el nombre pero no hay producto_id, es inválida
                if (!empty($fila['nombre']) && empty($fila['producto_id'])) {
                    $this->filasInvalidas[] = $index;
                    $todasValidas = false;
                }
            }
        }
        
        // Hay productos válidos si al menos hay uno
        $this->hayProductosValidos = $filasValidas > 0 && $todasValidas;
        
        // Disparar evento para indicar el estado al frontend
        $this->dispatchBrowserEvent('productos-validados', [
            'validos' => $this->hayProductosValidos,
            'cantidad' => $filasValidas,
            'filasInvalidas' => $this->filasInvalidas
        ]);
        
        return $this->hayProductosValidos;
    }

    public function render()
    {
        // Recalcular todos los subtotales antes de renderizar
        $this->recalcularTodosLosSubtotales();
        
        $totalGeneral = $this->calcularTotalGeneral();
        $this->totalGeneral = $totalGeneral; // Actualiza la propiedad totalGeneral

        return view('livewire.licitacion-detalle', [
            'totalGeneral' => $this->totalGeneral,
            'hayProductosValidos' => $this->hayProductosValidos,
            'filasInvalidas' => $this->filasInvalidas
        ]);
    }

    public function guardar()
    {
        // Verificamos que haya al menos una fila con producto seleccionado y todas las filas sean válidas
        if (!$this->validarProductos()) {
            // Si hay filas con nombres pero sin producto_id, mostrar un mensaje específico
            if (count($this->filasInvalidas) > 0) {
                $this->dispatchBrowserEvent('error-evento', [
                    'mensaje' => 'Hay productos que no se han seleccionado correctamente. Por favor, seleccione productos válidos de la lista desplegable.'
                ]);
            } else {
                // Si no hay productos en absoluto
                $this->dispatchBrowserEvent('error-evento', [
                    'mensaje' => 'Debe agregar al menos un producto para guardar.'
                ]);
            }
            // Detenemos la ejecución para evitar que se envíe el formulario
            return false;
        }

        // Eliminar filas vacías o incompletas antes de guardar
        $filasValidas = collect($this->filas)->filter(function ($fila) {
            return !empty($fila['producto_id']) && !empty($fila['nombre']);
        })->values()->toArray();
        
        if ($this->eslicitacion) {
            // Para licitaciones, usamos los eventos anteriores
            $this->dispatchBrowserEvent('set-detalle-json', [
                'detalle' => json_encode($filasValidas)
            ]);
            
            $this->dispatchBrowserEvent('productos-validados-listos');
        } else {
            // Para órdenes de compra, usamos el mismo formato de evento
            $this->dispatchBrowserEvent('set-detalle-json', [
                'detalle' => json_encode($filasValidas)
            ]);
            
            $this->dispatchBrowserEvent('detalleGuardado', [
                'detalle' => $filasValidas
            ]);
        }
        
        return true;
    }
}
