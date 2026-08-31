<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\MenuTrait;
use App\Traits\AlmacenesTraits;
use App\Models\Ubicaciones;
use Carbon\Carbon;
class UbicacionesController extends Controller
{
    
    use MenuTrait;
    use AlmacenesTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function pubicaciones(Request $request,int $id)
    {
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $ubicaciones =$this->Listadoubicacionesxidalmacen($id);
       
        return view('Inventarios.ubicaciones',compact('varpantallas','varsubmenus','ubicaciones','id'));
    }

    public function pinsertaubi(int $idalm, string $nomalm)
    {
        $almacenes = $this->Listadoalmacenes();
        $ubicaciones = $this->Listadotiposubi();
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        return view('Inventarios.nuevaubicacion',compact('varpantallas','varsubmenus',
        'almacenes','ubicaciones','idalm','nomalm'));
    }

    public function minsertaubi(Request $request)
    {

        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');

        $insertaubi = new Ubicaciones();
        $insertaubi->id_almacen = $request->post('id_almacen');
        $insertaubi->folio_interno = $request->post('folio_interno');
        $insertaubi->descripcion = $request->post('descripcion');
        $insertaubi->id_tipo_ubicacion = $request->post('id_tipo_ubicacion');
        $insertaubi->capacidad = $request->post('capacidad');
        $insertaubi->nivel = $request->post('nivel');
        $insertaubi->cordenadas = $request->post('cordenadas');
        $insertaubi->observaciones= $request->post('observaciones'); 
        $insertaubi->espacio = $request->post('espacio');
        $insertaubi->ubicacion = $request->post('ubicacion');
        $insertaubi->created_at = $fecha;
       if($insertaubi->save())
       {
        return redirect()->route('ubicaciondet',[$request->post('id_almacen')])->with("exito","realizado correctamente");
       }
       else
       {
        return back()->with("error","No se logro");
       }

       

    }

    public function pactualizaubi(Request $reques,int $idalm,int $idubi,string $nomalm)
    {
        $almacenes = $this->Listadoalmacenes();
        $ubicaciones = $this->Listadotiposubi();
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $Listaubixid = $this->Listaubixid($idubi);
        return view('Inventarios.actualizaubi',compact('varpantallas','varsubmenus','almacenes',
        'ubicaciones','Listaubixid','idalm','nomalm')); 
    }

    public function mactualizaubi(Request $request)
    {
        $idubi = $request->post('id');
         $date = Carbon::now();
        $fecha = $date->format('Y-m-d');

        $insertaubi = Ubicaciones::find($idubi);
        $insertaubi->id_almacen = $request->post('id_almacen');
        $insertaubi->folio_interno = $request->post('folio_interno');
        $insertaubi->descripcion = $request->post('descripcion');
        $insertaubi->id_tipo_ubicacion = $request->post('id_tipo_ubicacion');
        $insertaubi->capacidad = $request->post('capacidad');
        $insertaubi->nivel = $request->post('nivel');
        $insertaubi->cordenadas = $request->post('cordenadas');
        $insertaubi->observaciones= $request->post('observaciones'); 
        $insertaubi->espacio = $request->post('espacio');
        $insertaubi->ubicacion = $request->post('ubicacion');
        $insertaubi->created_at = $fecha;


       if($insertaubi->save())
       {
        return redirect()->route('ubicaciondet',[$request->post('id_almacen')])->with("exito","realizado correctamente");
       }
       else
       {
        return back()->with("error","No se logro");
       }
    }

  
}
