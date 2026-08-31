<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\MenuTrait;
use App\Traits\AlmacenesTraits;

class AlmacenController extends Controller
{

    use MenuTrait;
    use AlmacenesTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function almacenes()
    {
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $Listadoalmacenes = $this->Listadoalmacenes();
        return view('Almacenes.almacenes',compact('varpantallas','varsubmenus','Listadoalmacenes'));
    }

    public function pinsertaalm()
    {
        $Listadotiposalmacen = $this->Listadotiposalmacen();
        $Listadomunicipios= $this->Listadomunicipios();
        $Listadoempleadosalmacen= $this->Listadoempleadosalmacen();
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        return view('Almacenes.nuevoalmacen',compact('varpantallas','varsubmenus','Listadotiposalmacen','Listadomunicipios','Listadoempleadosalmacen'));
    }

    public function minsertaalm(Request $request)
    {
        $request->validate([
            'comentarios' => ['required', 'string', 'min:3', 'max:100'],
        ], [
            'comentarios.required' => 'Los comentarios son obligatorios.',
            'comentarios.min' => 'Los comentarios deben tener al menos :min caracteres.',
            'comentarios.max' => 'Los comentarios no pueden superar :max caracteres.',
        ]);

        $InsertaroActualizaalmacen = $this->InsertaroActualizaalmacen('Inserta',0, 
        $request->post('folio_interno'),
        $request->post('id_tipo_almacen'),
        $request->post('id_municipio'),
        $request->post('id_encargado'),
        $request->post('direccion'),
        $request->post('codigo_postal'),
        $request->post('telefono'),
        $request->post('correo_electronico'),
        $request->post('contacto'),
        $request->post('capacidad'),
        $request->post('comentarios'));
        
        if($InsertaroActualizaalmacen > 0)
        {
            return redirect()->route('inventario')->with("exito","realizado correctamente");
        }
        else
        {
            return back()->with("error","error");      
        }
    }

    public function pactualizaalmacen(Request $request,int $id)
    {
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $Listadoalmacenxid= $this->Listadoalmacenxid($id);
        $Listadotiposalmacen = $this->Listadotiposalmacen();
        $Listadomunicipios= $this->Listadomunicipios();
        $Listadoempleadosalmacen= $this->Listadoempleadosalmacen();
        return view('Almacenes.actualizaalmacen',compact('varpantallas','varsubmenus','Listadoalmacenxid','Listadotiposalmacen','Listadomunicipios','Listadoempleadosalmacen'));
    }

    public function mactualizaalm(Request $request)
    {
        $request->validate([
            'comentarios' => ['required', 'string', 'min:3', 'max:100'],
        ], [
            'comentarios.required' => 'Los comentarios son obligatorios.',
            'comentarios.min' => 'Los comentarios deben tener al menos :min caracteres.',
            'comentarios.max' => 'Los comentarios no pueden superar :max caracteres.',
        ]);

        $id = $request->post('id');

        $InsertaroActualizaalmacen = $this->InsertaroActualizaalmacen('Actualiza',$id, 
        $request->post('folio_interno'),
        $request->post('id_tipo_almacen'),
        $request->post('id_municipio'),
        $request->post('id_encargado'),
        $request->post('direccion'),
        $request->post('codigo_postal'),
        $request->post('telefono'),
        $request->post('correo_electronico'),
        $request->post('contacto'),
        $request->post('capacidad'),
        $request->post('comentarios'));
        
        if($InsertaroActualizaalmacen > 0)
        {
            return redirect()->route('inventario')->with("exito","realizado correctamente");
        }
        else
        {
            return back()->with("error","error");      
        }
    }

    /**
     * Busca productos con existencia y devuelve almacén + ubicación.
     */
    public function buscarExistenciasProducto(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([
                'success' => true,
                'resultados' => [],
                'message' => 'Escribe al menos 2 caracteres para buscar.',
            ]);
        }

        $rows = DB::table('tblexistencias as e')
            ->join('tblproductos as p', 'p.id', '=', 'e.id_producto')
            ->join('tblalmacenes as a', 'a.id', '=', 'e.id_almacen')
            ->join('tblubicaciones as u', 'u.id', '=', 'e.id_ubicacion')
            ->leftJoin('tblunidadesmedida as um', 'um.id', '=', 'p.id_unidad_medida')
            ->select([
                'p.id as id_producto',
                'p.nombre',
                'p.sku',
                'p.codigo_barras',
                'a.id as id_almacen',
                'a.folio_interno as almacen',
                'u.id as id_ubicacion',
                'u.folio_interno as ubicacion_folio',
                'u.ubicacion',
                'u.espacio',
                'u.nivel',
                'u.descripcion as ubicacion_descripcion',
                'um.nombre as unidad',
                'e.cantidad_existente',
                'e.cantidad_reservada',
                DB::raw('GREATEST(0, COALESCE(e.cantidad_existente,0) - COALESCE(e.cantidad_reservada,0)) as disponible'),
            ])
            ->where(function ($sub) use ($q) {
                $sub->where('p.nombre', 'like', "%{$q}%")
                    ->orWhere('p.descripcion', 'like', "%{$q}%")
                    ->orWhere('p.sku', 'like', "%{$q}%")
                    ->orWhere('p.codigo_barras', 'like', "%{$q}%");
            })
            ->whereRaw('GREATEST(0, COALESCE(e.cantidad_existente,0) - COALESCE(e.cantidad_reservada,0)) > 0')
            ->orderBy('p.nombre')
            ->orderBy('a.folio_interno')
            ->orderBy('u.folio_interno')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'resultados' => $rows,
            'total' => $rows->count(),
        ]);
    }

}
