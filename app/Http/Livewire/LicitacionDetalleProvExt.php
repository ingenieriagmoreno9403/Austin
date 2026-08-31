<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Producto;
use App\Models\UnidadMedida;
use Illuminate\Support\Facades\DB;

class LicitacionDetalleProvExt extends Component
{
    public $filas = [];
    public $resultados = [];
    public $buscando = [];
    public $totalGeneral = 0;
    public $sololectura = false;
    public $licitacion_id;
    public $proveedor_id;
    public $fleteInterno = 0;
    public $fleteExterno = 0;
    public $fleteUniversal = 0;
    public $margenGlobal = 0;

    protected $listeners = ['calcularSubtotales' => 'calcularTodosLosSubtotales'];

    public function mount($detalle = [], $licitacion_id = null, $proveedor_id = null, $fleteInterno = 0, $fleteExterno = 0)
    {
        $this->licitacion_id = $licitacion_id;
        $this->proveedor_id = $proveedor_id;
        $this->fleteInterno = $fleteInterno;
        $this->fleteExterno = $fleteExterno;

        // Cargar el flete universal desde la base de datos
        if ($licitacion_id && $proveedor_id) {
            $fleteDB = DB::table('tbllicitacionproducto_proveedor')
                ->where('licitacion_id', $licitacion_id)
                ->where('proveedor_id', $proveedor_id)
                ->whereNotNull('envio')
                ->where('envio', '>', 0)
                ->value('envio');
            
            $this->fleteUniversal = $fleteDB ? floatval($fleteDB) : 0;
            
            // Cargar el margen global desde la base de datos
            try {
                // Intentar buscar en tbllicitacion_proveedor primero
                $margenGlobalDB = DB::table('tbllicitacion_proveedor')
                    ->where('licitacion_id', $licitacion_id)
                    ->where('proveedor_id', $proveedor_id)
                    ->value('margen_global');
                
                if ($margenGlobalDB !== null && $margenGlobalDB !== '') {
                    $this->margenGlobal = floatval($margenGlobalDB);
                } else {
                    // Si no se encontró, buscar en tbllicitacionproducto_proveedor
                    $margenGlobalDB = DB::table('tbllicitacionproducto_proveedor')
                        ->where('licitacion_id', $licitacion_id)
                        ->where('proveedor_id', $proveedor_id)
                        ->whereNotNull('margen_global')
                        ->value('margen_global');
                    
                    if ($margenGlobalDB !== null && $margenGlobalDB !== '') {
                        $this->margenGlobal = floatval($margenGlobalDB);
                    }
                }
            } catch (\Exception $e) {
                // Si la columna no existe, dejar el valor en 0
                // El usuario necesitará agregar la columna margen_global a la tabla tbllicitacion_proveedor
                $this->margenGlobal = 0;
            }
        }

        // Default structure for a row
        $defaultRow = [
            'producto_id' => '',
            'nombre' => '',
            'cantidad' => 1,
            'umed' => '',
            'observaciones' => '',
            'costo' => 0,
            'margen' => 0,
            'margencost' => 0,
            'envio' => 0,
            'subtotal' => 0,
            'total' => 0,
            'marca' => '',
        ];

        if (empty($detalle)) {
            $this->filas = [$defaultRow];
        } else {
            // Ensure each row has all required fields
            $this->filas = array_map(function($row) use ($defaultRow) {
                return array_merge($defaultRow, $row);
            }, $detalle);
        }
        
        // Calculate initial subtotals
        $this->calcularTodosLosSubtotales();
    }

    public function updatedFleteUniversal()
    {
        $this->calcularTodosLosSubtotales();
    }

    public function updatedFleteInterno()
    {
        $this->actualizarFleteUniversal();
    }

    public function updatedFleteExterno()
    {
        $this->actualizarFleteUniversal();
    }

    public function actualizarFleteUniversal()
    {
        $this->fleteUniversal = floatval($this->fleteInterno) + floatval($this->fleteExterno);
        $this->calcularTodosLosSubtotales();
    }

    public function updatedMargenGlobal($value)
    {
        foreach ($this->filas as $i => $fila) {
            $this->filas[$i]['margen'] = $value;
        }
        $this->calcularTodosLosSubtotales();
    }
    
    public function actualizarMargenGlobalHidden()
    {
        // Este método se llama cuando cambia el margen global para actualizar el campo hidden
        // El valor ya está actualizado en $this->margenGlobal por el binding de Livewire
    }

    public function calcularSubtotal($index)
    {
        // Check if index exists in the filas array
        if (!isset($this->filas[$index])) {
            return;
        }
        
        // Check if required keys exist
        if (!isset($this->filas[$index]['cantidad']) || !isset($this->filas[$index]['costo']) 
        || !isset($this->filas[$index]['margen'])) {
            // Initialize missing keys with default values
            $this->filas[$index]['cantidad'] = $this->filas[$index]['cantidad'] ?? 0;
            $this->filas[$index]['costo'] = $this->filas[$index]['costo'] ?? 0;
            $this->filas[$index]['margen'] = $this->filas[$index]['margen'] ?? 0;
        }
        
        // Make sure values are numeric
        $cantidad = is_numeric($this->filas[$index]['cantidad']) ? $this->filas[$index]['cantidad'] : 0;
        $costo = is_numeric($this->filas[$index]['costo']) ? $this->filas[$index]['costo'] : 0;
        $margen = is_numeric($this->filas[$index]['margen']) ? $this->filas[$index]['margen'] : 0;
        $fleteUniversal = is_numeric($this->fleteUniversal) ? $this->fleteUniversal : 0;
        
        // Contar solo las partidas con costo unitario mayor a 0
        $partidasConCosto = 0;
        foreach ($this->filas as $fila) {
            $costoFila = is_numeric($fila['costo']) ? $fila['costo'] : 0;
            if ($costoFila > 0) {
                $partidasConCosto++;
            }
        }
        
        // Calcular el flete por producto basado en el flete universal
        // Solo distribuir entre partidas con costo mayor a 0
        $envio = 0;
        if ($partidasConCosto > 0 && $costo > 0) {
            $envio = round(($fleteUniversal / $partidasConCosto) / max($cantidad, 1), 2);
        }

        // Calculate and update subtotal y total
        // Nueva fórmula: Cost + Margen = Costo U. * (100 / (100 - margen))
        //Cost + Margen = Costo U. * (100 / (100 - margen))

        $margenOriginal = $margen; // Guardar el margen original antes de convertir
        $margen = $margen / 100;
        
        // Calcular margencost con la nueva fórmula
        if ($margenOriginal >= 100) {
            // Evitar división por cero si el margen es 100% o mayor
            $this->filas[$index]['margencost'] = 0;
        } else {
            // Redondear el factor a 2 decimales antes de multiplicar
            $factor = round((100 / (100 - $margenOriginal)), 2);
            $this->filas[$index]['margencost'] = round($costo * $factor, 2);
        }
        
        $this->filas[$index]['subtotal'] = round($costo + $envio, 2);
        $this->filas[$index]['total'] = round(($costo + $envio) * $cantidad, 2);
        $this->filas[$index]['enviototal'] = $envio;
        $this->filas[$index]['envio'] = $fleteUniversal; // Guardar el flete universal en cada fila
    }
    
    public function calcularTodosLosSubtotales()
    {
        foreach (array_keys($this->filas) as $index) {
            $this->calcularSubtotal($index);
        }
    }

    public function updatedFilas($value, $key)
    {
        // Extract index from the key (format is 'filas.0.costo')
        $parts = explode('.', $key);
        if (count($parts) < 3) {
            return; // Invalid key format
        }
        
        $index = $parts[1];
        $field = $parts[2];
        
        // Only recalculate if cost or quantity changed
        if ($field === 'costo' || $field === 'cantidad' || $field === 'margen') {
            $this->calcularSubtotal($index);
        }
    }

    public function render()
    {
        return view('livewire.licitacion-detalle-prov-ext');
    }
}
