<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Traits\MenuTrait;
use App\Traits\GlobalTraits;
use Illuminate\Http\Request;
use App\Traits\SistemasTraits;
use Illuminate\Support\Carbon;
use App\Traits\DatosimpleTraits;

class CompraController extends Controller
{

    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use GlobalTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();

            $varlista = Compra::all();
         
            $date = Carbon::now();
            $date = $date->format('Y-m-d');
            $idusuario=auth()->user()->id;

            return view('compra.cotizacion',compact('varpantallas','varsubmenus','varlista'));
        } catch(\Illuminate\Database\QueryException $ex)
        {
          return back()->with("warningBD","no guardado correctamente");
        }
    }

    public function create()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();

            return view('compra.create', compact('varpantallas', 'varsubmenus'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "No se pudo cargar la vista correctamente");
        }
    }

    public function store(Request $request){
        try{
            $compra = Compra::create($request->all());
            // si existe el objeto, se realizo bien la insercion
            if($compra){
                return redirect()->route('compras')->with("success","¡Se guardaron los cambios correctamente!");
            }else{
                return redirect()->route('compras')->with("warning","¡No se guardaron los cambios correctamente!");}
        } catch(\Illuminate\Database\QueryException $ex)
        {  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function edit($id){
        try{
            // traer la informacion de los datos a enviar a edicion
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();

            $date = Carbon::now();
            $date = $date->format('Y-m-d');

            //$obtenerempleado = $this->obtenerlistaempleadoid($id);
            $compra = Compra::find($id);
            // $permisos = $this->forpermisos('actualizar_empleados');  

            // if($permisos=="actualizar_empleados")
            // {
                return view('compra.edit',compact('varpantallas','varsubmenus','compra'));
            // }
            // else{
            //     return redirect()->route('verempleados')->with("Errorpermisos","No se logro");  
            // }
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }


    
    public function update(Request $request,$id){
        try{
            // traer la informacion de los datos a enviar a edicion
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();

            $date = Carbon::now();
            $date = $date->format('Y-m-d');

            //$obtenerempleado = $this->obtenerlistaempleadoid($id);
            $compra = Compra::find($id);
            $compra->update($request->all());
            if($compra){
                return redirect()->route('compras')->with("success","¡Se guardaron los cambios correctamente!");
            }else{
                return redirect()->route('compras')->with("warning","¡No se guardaron los cambios correctamente!");}

        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function destroy($id){
        try{
            $compra = Compra::find($id);
            $compra->delete();
            return redirect()->route('compras')->with("success","¡Se elimino correctamente!");
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }


}
