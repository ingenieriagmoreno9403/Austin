<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ProductosTraits;
use App\Traits\MenuTrait;
use App\Traits\ProveedoresTraits;
use Carbon\Carbon;
use App\Traits\SistemasTraits;
use Illuminate\Http\JsonResponse;

class ProductosController extends Controller
{

    use MenuTrait;
    use ProductosTraits;
    use ProveedoresTraits;
    use SistemasTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function productos()
    {
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $Listadoproductos = $this->Listadoproductos();
        $permisos1 = $this->forpermisos('crear_product');
        $permisos2 = $this->forpermisos('edit_product');

        return view('Productos.productos',compact('varpantallas','varsubmenus','Listadoproductos','permisos1','permisos2'));
    }

    public function pinsertaroductos()
    {

        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $Listacategoriaproductos = $this->Listacategoriaproductos();
        $Listadounidadesmedida = $this->Listadounidadesmedida();
        $listadoprovedores = $this->Listadoproveedores();

        return view('Productos.nuevoproducto',compact('varpantallas','varsubmenus','Listacategoriaproductos','Listadounidadesmedida','listadoprovedores'));
    }

    /**
     * Alta rápida de categoría de producto (AJAX desde nuevo producto).
     */
    public function storeCategoriaProducto(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
        ]);
        $nombre = trim($request->input('nombre'));
        try {
            $res = $this->insertarCategoriaProducto($nombre);
            $id = $res['id'];
            if ($id < 1) {
                return response()->json([
                    'message' => 'No se pudo registrar la categoría.',
                ], 422);
            }

            return response()->json([
                'message' => $res['creado'] ? 'Categoría registrada.' : 'Esa categoría ya existía; se seleccionó en la lista.',
                'id' => $id,
                'nombre' => $nombre,
                'creado' => $res['creado'],
            ], $res['creado'] ? 201 : 200);
        } catch (\Throwable $e) {
            \Log::error('storeCategoriaProducto: '.$e->getMessage());

            return response()->json([
                'message' => 'No se pudo guardar la categoría en base de datos.',
            ], 500);
        }
    }

    public function minsertapro(Request $request)
    {
        $descripcion = $this->validarDescripcionProducto($request);

        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $piezasxunio = 0;

        if($request->get('piezasxuni')=='')
        {
            $piezasxunio = 0;
        }
        else
        {
            $piezasxunio= $request->get('piezasxuni');
        }

        $validasku =$this->validasku($request->get('sku'));
        $Nombrefoto1 = 'F1'.$request->get('sku');
        $Nombrefoto2 = 'F2'.$request->get('sku');
        $Nombrefoto3 = 'F3'.$request->get('sku');
        $nombrecarpeta = $request->get('sku');
        

        $Guardarimgperfil1 = $this->Guardarimgproductos('ruta_img1',$nombrecarpeta,$Nombrefoto1,$request);
        $Guardarimgperfil2 = $this->Guardarimgproductos('ruta_img2',$nombrecarpeta,$Nombrefoto2,$request);
        $Guardarimgperfil3 = $this->Guardarimgproductos('ruta_img3',$nombrecarpeta,$Nombrefoto3,$request);



        if($validasku > 0)
        {
            return back()->with("Errorskuocodb","no guardado correctamente"); 
        }
    

        $insertarproducto = $this->Insertaprodcuto('Inserta', 0,
        $descripcion,
        $this->textoProducto($request->get('sku')),
        $this->textoProducto($request->get('codigo_barras')),
        $this->decimalProducto($request->get('precio_unitario')),
        $this->textoProducto($request->get('nombre')),
        $this->enteroProducto($request->get('id_categoria')),
        $this->enteroProducto($request->get('id_unidad_medida')),
        $this->enteroProducto($request->get('id_proveedor')),
        $this->decimalProducto($request->get('costo_compra')),
        $this->decimalProducto($request->get('costo_venta')),
        $this->decimalProducto($request->get('minima_existencia')),
        $this->decimalProducto($request->get('maxima_existencia')),
        $fecha,
        $Guardarimgperfil1,
        $Guardarimgperfil2,
        $Guardarimgperfil2,
        $this->enteroProducto($piezasxunio));

        if($insertarproducto > 0)
        {
            return redirect()->route('productos')->with("exito","realizado correctamente");
        }
        else
        {
            return back()->with("error","no guardado correctamente"); 
        }
    }

    public function pactualizaprod(Request $request,int $id)
    {
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $Listacategoriaproductos = $this->Listacategoriaproductos();
        $Listadounidadesmedida = $this->Listadounidadesmedida();
        $listadoprovedores = $this->Listadoproveedores();
        $Listadoproductosxid = $this->Listadoproductosxid($id);
        

        return view('Productos.actualizaproducto',compact('varpantallas','varsubmenus','Listacategoriaproductos','Listadounidadesmedida','listadoprovedores','Listadoproductosxid'));

    }


    public function mactualizaprod(Request $request)
    {
        $descripcion = $this->validarDescripcionProducto($request);

        $idp =$request->get('id');
        $listadopid = $this->Listadoproductosxid($idp);
        $acfoto1 ="";
        $acfoto2 ="";
        $acfoto3 ="";

        foreach($listadopid as $lts)
        {
            if(is_null($lts->ruta_img1))
            {
                $acfoto1 = "null";
            }else{
                $acfoto1 = $lts->ruta_img1;
            }

            if(is_null($lts->ruta_img2))
            {
                $acfoto2 = "null";
            }else{
                $acfoto2 = $lts->ruta_img2;
            }

            if(is_null($lts->ruta_img3))
            {
                $acfoto3 = "null";
            }else{
                $acfoto3 = $lts->ruta_img3;
            }
        }

        // return $acfoto1.$acfoto2.$acfoto3;

        // $Nombrefoto1 = 'F1'.$request->get('sku');
        // $Nombrefoto2 = 'F2'.$request->get('sku');
        // $Nombrefoto3 = 'F3'.$request->get('sku');
        $piezasxunio = 0;

        if($request->get('piezasxuni')=='')
        {
            $piezasxunio = 0;
        }
        else
        {
            $piezasxunio= $request->get('piezasxuni');
        }


        $nombrecarpeta = $request->get('sku');

        $img1old = $_FILES["ruta_img1"];
        $img1old2 = $_FILES["ruta_img2"];
        $img1old3 = $_FILES["ruta_img3"];


        if($request->hasFile("ruta_img1"))
        {
            $Nombrefoto1 = 'F1'.$request->get('sku');
            $Guardarimgperfil1 = $this->Guardarimgproductos('ruta_img1',$nombrecarpeta,$Nombrefoto1,$request);
           
        }
        else
        {
            $Guardarimgperfil1 =$acfoto1;
        }



        if($request->hasFile("ruta_img2"))
        {
            $Nombrefoto2 = 'F2'.$request->get('sku');
            $Guardarimgperfil2 = $this->Guardarimgproductos('ruta_img2',$nombrecarpeta,$Nombrefoto2,$request);
        }
           
     
        else
        {

            $Guardarimgperfil2 =$acfoto2;
        }

            



        if($request->hasFile("ruta_img3"))
        {
            $Nombrefoto3 = 'F3'.$request->get('sku');
            $Guardarimgperfil3 = $this->Guardarimgproductos('ruta_img3',$nombrecarpeta,$Nombrefoto3,$request);
            
        }
        else
        {
            $Guardarimgperfil3 = $acfoto3;
        }

  

        $id = $this->enteroProducto($request->get('id'));
        $insertarproducto = $this->Insertaprodcuto('Actualiza', $id,
        $descripcion,
        $this->textoProducto($request->get('sku')),
        $this->textoProducto($request->get('codigo_barras')),
        $this->decimalProducto($request->get('precio_unitario')),
        $this->textoProducto($request->get('nombre')),
        $this->enteroProducto($request->get('id_categoria')),
        $this->enteroProducto($request->get('id_unidad_medida')),
        $this->enteroProducto($request->get('id_proveedor')),
        $this->decimalProducto($request->get('costo_compra')),
        $this->decimalProducto($request->get('costo_venta')),
        $this->decimalProducto($request->get('minima_existencia')),
        $this->decimalProducto($request->get('maxima_existencia')),
        $this->textoProducto($request->get('fecha_entrada')),
        $Guardarimgperfil1,
        $Guardarimgperfil2,
        $Guardarimgperfil3,
        $this->enteroProducto($piezasxunio));

        if($insertarproducto > 0)
        {
            return redirect()->route('productos')->with("exito","realizado correctamente");
        }
        else
        {
            return back()->with("error","no guardado correctamente"); 
        }
    }

    private function validarDescripcionProducto(Request $request): string
    {
        $validated = $request->validate([
            'descripcion' => ['required', 'string', 'min:3', 'max:100'],
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.min' => 'La descripción debe tener al menos 3 caracteres.',
            'descripcion.max' => 'La descripción no puede exceder 100 caracteres.',
        ]);

        return trim($validated['descripcion']);
    }

    private function enteroProducto(mixed $valor): int
    {
        if ($valor === null || $valor === '') {
            return 0;
        }

        return (int) $valor;
    }

    private function decimalProducto(mixed $valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        return (float) $valor;
    }

    private function textoProducto(mixed $valor): string
    {
        return trim((string) ($valor ?? ''));
    }
}
