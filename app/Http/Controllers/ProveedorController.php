<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Proveedorproducto;
use Illuminate\Http\Request;
use App\Models\facturify;
use App\Models\Vistas;
use App\Models\Acciones;
use App\Models\usuario_pantallas;
use App\Models\usuario_acciones;
use App\Traits\MenuTrait;
use Carbon\Carbon;
use App\Traits\DatosimpleTraits;
use App\Traits\SistemasTraits;
use App\Models\perfil_acciones;
use App\Models\perfiles;
use App\Models\Proveedores;
use App\Models\UserSucursal;
use App\Support\HomologacionProveedor;
use Illuminate\Support\Arr;
use DB;
use Log;

class ProveedorController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function indexproveedores()
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varlistausers = $this->obtenerusuarios();
        $proveedores = DB::select("SELECT * FROM tblprovedores where estado = 'A';");
        $alertasDocumentos = $this->alertasDocumentos();
        $alertasContratos = $this->alertasContratos();
        $categorias = $this->categoriasProveedor();
        $lista_proveedores = DB::select("SELECT u.id, u.name
            FROM users u
            LEFT JOIN tblprovedores p ON u.id = p.id_usuario
            WHERE u.tipo = 'ext'
        AND p.id_usuario IS NULL;");

        return view('proveedores/proveedoresIndex', compact('varpantallas', 'varsubmenus', 'varlistausers', 'proveedores','lista_proveedores','alertasDocumentos','alertasContratos','categorias'));
    }

    public function historialproveedores()
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varlistausers = $this->obtenerusuarios();
        $proveedores = DB::select("SELECT * FROM tblprovedores where estado = 'A';");

        return view('proveedores/historial', compact('varpantallas', 'varsubmenus', 'varlistausers', 'proveedores'));
    }

    public function licitacionesproveedores()
    {
        try
        {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varlistausers = $this->obtenerusuarios();
        $proveedores = DB::select("SELECT * FROM tblprovedores where estado = 'A';");

        return view('proveedores/licitaciones', compact('varpantallas', 'varsubmenus', 'varlistausers', 'proveedores'));
        } 
        catch (\Illuminate\Database\QueryException $ex) 
        {
        Log::info($ex->getMessage());
        return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function insertarproveedor(Request $request)
    {
        try{

            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varlistausers = $this->obtenerusuarios();
        

            $nombre = $request->post('nombre');
            $telefono = $request->post('telefono');
            $direccion = $request->post('direccion');
            $correo = $request->post('correo');
            $nit = $request->post('nit');
            $giro = $request->post('giro');
            $rfc = $request->post('rfc');
            $modo_pago = $request->post('modo_pago');
            $dias_credito = $request->post('modo_pago') == 'credito' ? $request->post('dias_credito') : null;

            $tblproveedorres = new Proveedores();
            $tblproveedorres->nombre = $nombre;
            $tblproveedorres->telefono = $telefono;
            $tblproveedorres->otrosconceptos1 = $correo;
            $tblproveedorres->direccion = $direccion;
            $tblproveedorres->nit = $nit;
            $tblproveedorres->giro = $giro;
            $tblproveedorres->rfc = $rfc;
            $tblproveedorres->modo_pago = $modo_pago;
            $tblproveedorres->dias_credito = $dias_credito;
            $tblproveedorres->calificacion_proveedor = $request->post('evaluacion');
            $estado = $request->post('estado');
            $tblproveedorres->estado = in_array($estado, ['A', 'I'], true) ? $estado : 'A';
            $tblproveedorres->homologacion = HomologacionProveedor::normalizar($request->post('homologacion', 'aprobado'));
            $tblproveedorres->preferido = $request->post('preferido') ? 1 : 0;
            $tblproveedorres->contrato_valor = $request->filled('contrato_valor') ? $request->post('contrato_valor') : null;
            $tblproveedorres->contrato_inicio = $request->filled('contrato_inicio') ? $request->post('contrato_inicio') : null;
            $tblproveedorres->contrato_fin = $request->filled('contrato_fin') ? $request->post('contrato_fin') : null;
            $tblproveedorres->categoria_id = $this->resolverCategoria($request);
            $tblproveedorres->id_usuario = $request->post('userid');
            $tblproveedorres->save();

            if ($request->hasFile('contrato_archivo') && $request->file('contrato_archivo')->isValid()) {
                $tblproveedorres->contrato_archivo = $this->guardarArchivoProveedor($request->file('contrato_archivo'), (int) $tblproveedorres->id, 'contrato');
                $tblproveedorres->save();
            }
            $this->guardarDocumentosYAcciones($request, (int) $tblproveedorres->id);

            $proveedores = DB::select("SELECT * FROM tblprovedores where estado = 'A';");

            $alertasDocumentos = $this->alertasDocumentos();
            $alertasContratos = $this->alertasContratos();
            $categorias = $this->categoriasProveedor();
            return view('proveedores/proveedoresIndex', compact('varpantallas', 'varsubmenus', 'varlistausers', 'proveedores','lista_proveedores','alertasDocumentos','alertasContratos','categorias'));
        } 
        catch (\Illuminate\Database\QueryException $ex){
            Log::info($ex->getMessage());
            return back()->with("warningBD", "no guardado correctamente");
        }

    }
    

    public function editarproveedores($id)
    {
        try{
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varlistausers = $this->obtenerusuarios();
            $idproveedor = $id;

            $proveedores = DB::select("SELECT * FROM tblprovedores where id = ?;",[$id]);
            $documentos = DB::table('tblproveedor_documentos')->where('proveedor_id', $id)->orderBy('vigencia')->get();
            $acciones = DB::table('tblproveedor_acciones')->where('proveedor_id', $id)->orderByDesc('id')->get();
            $categorias = $this->categoriasProveedor();
            return view('proveedores/edit_proveedor', compact('varpantallas', 'varsubmenus', 'varlistausers', 'proveedores','idproveedor','documentos','acciones','categorias'));
        
        } catch (\Illuminate\Database\QueryException $ex){
            Log::info($ex->getMessage());
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function updateproveedor(Request $request, $id)
    {
        try{
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varlistausers = $this->obtenerusuarios();

            $nombre = $request->post('nombre');
            $telefono = $request->post('telefono');
            $correo = $request->post('correo');
            $direccion = $request->post('direccion');
            $nit = $request->post('nit');
            $giro = $request->post('giro');
            $rfc = $request->post('rfc');
            $calificacion = $request->post('evaluacion');
            $modo_pago = $request->post('modo_pago');
            $dias_credito = $request->post('modo_pago') == 'credito' ? $request->post('dias_credito') : null;

            $tblproveedor = Proveedores::find($id);
            $tblproveedor->nombre = $nombre;
            $tblproveedor->telefono = $telefono;
            $tblproveedor->otrosconceptos1 = $correo;
            $tblproveedor->direccion = $direccion;
            $tblproveedor->nit = $nit;
            $tblproveedor->giro = $giro;
            $tblproveedor->rfc = $rfc;
            $tblproveedor->calificacion_proveedor = $calificacion;
            $tblproveedor->modo_pago = $modo_pago;
            $tblproveedor->dias_credito = $dias_credito;
            $tblproveedor->homologacion = HomologacionProveedor::normalizar($request->post('homologacion', 'aprobado'));
            $tblproveedor->preferido = $request->post('preferido') ? 1 : 0;
            $tblproveedor->contrato_valor = $request->filled('contrato_valor') ? $request->post('contrato_valor') : null;
            $tblproveedor->contrato_inicio = $request->filled('contrato_inicio') ? $request->post('contrato_inicio') : null;
            $tblproveedor->contrato_fin = $request->filled('contrato_fin') ? $request->post('contrato_fin') : null;
            $tblproveedor->categoria_id = $this->resolverCategoria($request);

            if ($request->hasFile('contrato_archivo') && $request->file('contrato_archivo')->isValid()) {
                $tblproveedor->contrato_archivo = $this->guardarArchivoProveedor($request->file('contrato_archivo'), (int) $id, 'contrato');
            }

            $tblproveedor->save();
            $this->guardarDocumentosYAcciones($request, (int) $id);

            $proveedores = DB::select("SELECT * FROM tblprovedores where id = ?;",[$id]);

            return redirect()->route('proveedores')->with("success","success");
        } catch (\Illuminate\Database\QueryException $ex){
            Log::info($ex->getMessage());
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function EliminarProveedor($id)
    {
        try{
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varlistausers = $this->obtenerusuarios();
            
            $tblproveedor = Proveedores::find($id);
            $tblproveedor->delete();

            $proveedores =  DB::select("SELECT * FROM tblprovedores where estado = 'A';");
            return redirect()->route('proveedores')->with("success","success");

        } catch (\Illuminate\Database\QueryException $ex){
            Log::info($ex->getMessage());
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function ProductosProveedor(Request $request)
    {
        //
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varlistausers = $this->obtenerusuarios();
        $idproveedor = request('id');
       /*obtener los productos generales por proveedor */
        $productosproveedor = DB::select("SELECT * FROM `tbl_proveedor_producto` where provvedor_id = ?",[$idproveedor]);
        $productos = DB::select("SELECT * FROM `tblproductos`");
    

        return view('proveedores/proveedores_productos', compact('varpantallas', 'varsubmenus', 'varlistausers','productos','idproveedor','productosproveedor' ));

    }

    public function InsertProductoProveedor(Request $request, $idproveedor)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varlistausers = $this->obtenerusuarios();
        $idproducto =$request->post('nombre');
        $idproveedor = request('id');
       /*obtener los productos generales */
        $productos = DB::select("SELECT * FROM `tblproductos` where id = ?",[$idproducto]);
        foreach($productos as $ltsproductos)
        {
        $proveedro_producto = new Proveedorproducto();
        $proveedro_producto->nombre = $ltsproductos->nombre;
        $proveedro_producto->categoria = $ltsproductos->id_categoria;
        $proveedro_producto->unidad_medida = $ltsproductos->id_unidad_medida;
        $proveedro_producto->costo = $request->post('costo');
        $proveedro_producto->comentarios = $request->post('comentario');
        $proveedro_producto->descripcion = $ltsproductos->descripcion;
        $proveedro_producto->moneda = $request->post('moneda');
        $proveedro_producto->producto_id = $idproducto;
        $proveedro_producto->provvedor_id = $idproveedor;
        $proveedro_producto->save();


        }

    

        return view('proveedores/proveedores_productos', compact('varpantallas', 'varsubmenus', 'varlistausers','productos','idproveedor' ));

        
    }

    public function EditarProductoProveedor(Request $request)
    {

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varlistausers = $this->obtenerusuarios();
        $idproveedor = request('id');

        $monedas = DB::select("SELECT * FROM `tblmonedas`");
        $medidas = DB::select("SELECT * FROM `tblunidadesmedida`");
        $categoriasproductos = DB::select("select * from tblcategoria_producto;");  

        $producto = DB::select("SELECT pp.id as id, pp.nombre, cp.nombre as categoria, cp.id as idcat, um.nombre as unidad_medida, um.id as umid, pp.costo as costo, pp.descripcion, m.nombre as moneda, m.abreviacion as abrevia, m.id as idm  from tbl_proveedor_producto pp 
  join tblcategoria_producto cp on pp.categoria = cp.id 
  join  tblunidadesmedida um on pp.unidad_medida = um.id
join tblmonedas m on pp.moneda = m.id
  where pp.id = ?;",[$idproveedor]);


        return view('proveedores/edit_producto_proveedor', compact('varpantallas', 'varsubmenus', 'varlistausers', 'producto','idproveedor','monedas','medidas','categoriasproductos'));
        

        try
        {
   
    } catch (\Illuminate\Database\QueryException $ex) 
    {
        Log::info($ex->getMessage());
        return back()->with("warningBD", "no guardado correctamente");
    }
    }

    public function updateprod_prove(Request $request, $id)
    {
      
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varlistausers = $this->obtenerusuarios();

        $nombre = $request->post('nombre');
        $categoria = $request->post('categoria');
        $costo = $request->post('costo');
        $medida = $request->post('medida');
        $moneda = $request->post('moneda');
        $descripcion =$request->post('descripcion');
        $comentarios =$request->post('comentarios');

        $proveedro_producto = Proveedorproducto::find($id);
        $proveedro_producto->nombre = $nombre; 
        $proveedro_producto->categoria = $categoria;
        $proveedro_producto->unidad_medida = $medida;
        $proveedro_producto->costo = $costo ;
        $proveedro_producto->comentarios = $comentarios;
        $proveedro_producto->descripcion = $descripcion;
        $proveedro_producto->$moneda;
        $proveedro_producto->save();

        $proveedores = DB::select("SELECT * FROM tblprovedores where id = ?;",[$id]);
        $proveedores = DB::select("SELECT * FROM tblcategoria_producto where id = ?",[$categoria]);
        $idproveedor = request('id');

        $monedas = DB::select("SELECT * FROM `tblmonedas`");
        $medidas = DB::select("SELECT * FROM `tblunidadesmedida`");
        $categoriasproductos = DB::select("select * from tblcategoria_producto;");  

        $producto = DB::select("SELECT pp.id as id, pp.nombre, cp.nombre as categoria, cp.id as idcat, um.nombre as unidad_medida, um.id as umid, pp.costo as costo, pp.descripcion, m.nombre as moneda, m.abreviacion as abrevia, m.id as idm  from tbl_proveedor_producto pp 
  join tblcategoria_producto cp on pp.categoria = cp.id 
  join  tblunidadesmedida um on pp.unidad_medida = um.id
join tblmonedas m on pp.moneda = m.id
  where pp.id = ?;",[$idproveedor]);

        return view('proveedores/edit_producto_proveedor', compact('varpantallas', 'varsubmenus', 'varlistausers', 'proveedores', 'producto','idproveedor','monedas','medidas','categoriasproductos'));

    }

    /**
     * Insertar nueva persona de atención
     */
    public function insertarPersonaAtencion(Request $request)
    {
        try {
            $request->validate([
                'id_proveedor' => 'required|integer|exists:tblprovedores,id',
                'primer_nombre' => 'required|string|max:50',
                'segundo_nombre' => 'nullable|string|max:50',
                'apellido_paterno' => 'required|string|max:50',
                'apellido_materno' => 'nullable|string|max:50',
                'puesto' => 'required|string|max:100',
            ]);

            $personaAtencion = new \App\Models\ProveedoresAtencion();
            $personaAtencion->id_proveedor = $request->id_proveedor;
            $personaAtencion->primer_nombre = strtoupper($request->primer_nombre);
            $personaAtencion->segundo_nombre = $request->segundo_nombre ? strtoupper($request->segundo_nombre) : null;
            $personaAtencion->apellido_paterno = strtoupper($request->apellido_paterno);
            $personaAtencion->apellido_materno = $request->apellido_materno ? strtoupper($request->apellido_materno) : null;
            $personaAtencion->puesto = strtoupper($request->puesto);
            $personaAtencion->save();

            return redirect()->back()->with('success', 'Persona de atención agregada correctamente');
            
        } catch (\Illuminate\Database\QueryException $ex) {
            Log::error('Error al insertar persona de atención: ' . $ex->getMessage());
            return redirect()->back()->with('warningBD', 'Error al guardar la persona de atención');
        } catch (\Exception $e) {
            Log::error('Error inesperado al insertar persona de atención: ' . $e->getMessage());
            return redirect()->back()->with('warningBD', 'Error inesperado al guardar la persona de atención');
        }
    }

    /**
     * Actualizar persona de atención existente
     */
    public function actualizarPersonaAtencion(Request $request, $id)
    {
        try {
            $request->validate([
                'primer_nombre' => 'required|string|max:50',
                'segundo_nombre' => 'nullable|string|max:50',
                'apellido_paterno' => 'required|string|max:50',
                'apellido_materno' => 'nullable|string|max:50',
                'puesto' => 'required|string|max:100',
            ]);

            $personaAtencion = \App\Models\ProveedoresAtencion::findOrFail($id);
            $personaAtencion->primer_nombre = strtoupper($request->primer_nombre);
            $personaAtencion->segundo_nombre = $request->segundo_nombre ? strtoupper($request->segundo_nombre) : null;
            $personaAtencion->apellido_paterno = strtoupper($request->apellido_paterno);
            $personaAtencion->apellido_materno = $request->apellido_materno ? strtoupper($request->apellido_materno) : null;
            $personaAtencion->puesto = strtoupper($request->puesto);
            $personaAtencion->save();

            return response()->json(['success' => true, 'message' => 'Persona de atención actualizada correctamente']);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Error de validación', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\QueryException $ex) {
            Log::error('Error al actualizar persona de atención: ' . $ex->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar la persona de atención'], 500);
        } catch (\Exception $e) {
            Log::error('Error inesperado al actualizar persona de atención: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error inesperado al actualizar la persona de atención'], 500);
        }
    }

    /**
     * Eliminar persona de atención
     */
    public function eliminarPersonaAtencion($id)
    {
        try {
            $personaAtencion = \App\Models\ProveedoresAtencion::findOrFail($id);
            $personaAtencion->delete();

            return response()->json(['success' => true, 'message' => 'Persona de atención eliminada correctamente']);
            
        } catch (\Illuminate\Database\QueryException $ex) {
            Log::error('Error al eliminar persona de atención: ' . $ex->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar la persona de atención'], 500);
        } catch (\Exception $e) {
            Log::error('Error inesperado al eliminar persona de atención: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error inesperado al eliminar la persona de atención'], 500);
        }
    }

    public function agregarCategoria(Request $request)
    {
        $nombre = mb_substr(trim((string) $request->input('nombre', '')), 0, 120);
        if ($nombre === '') {
            return response()->json(['ok' => false, 'message' => 'Escribe la categoría.'], 422);
        }

        $existe = DB::table('tblcategoria_proveedor')
            ->whereRaw('UPPER(nombre) = ?', [mb_strtoupper($nombre)])
            ->first();
        if ($existe) {
            return response()->json(['ok' => true, 'id' => (int) $existe->id, 'nombre' => $existe->nombre]);
        }

        $id = DB::table('tblcategoria_proveedor')->insertGetId(['nombre' => $nombre]);

        return response()->json(['ok' => true, 'id' => (int) $id, 'nombre' => $nombre]);
    }

    private function categoriasProveedor()
    {
        return DB::table('tblcategoria_proveedor')->orderBy('nombre')->get();
    }

    private function resolverCategoria(Request $request): ?int
    {
        $nueva = trim((string) $request->input('categoria_nueva', ''));
        if ($nueva !== '') {
            $nueva = mb_substr($nueva, 0, 120);
            $existe = DB::table('tblcategoria_proveedor')
                ->whereRaw('UPPER(nombre) = ?', [mb_strtoupper($nueva)])
                ->first();
            if ($existe) {
                return (int) $existe->id;
            }

            return (int) DB::table('tblcategoria_proveedor')->insertGetId([
                'nombre' => $nueva,
            ]);
        }

        $id = (int) $request->input('categoria_id', 0);
        if ($id > 0 && DB::table('tblcategoria_proveedor')->where('id', $id)->exists()) {
            return $id;
        }

        return null;
    }

    private function alertasDocumentos()
    {
        return DB::table('tblproveedor_documentos as d')
            ->join('tblprovedores as p', 'p.id', '=', 'd.proveedor_id')
            ->whereNotNull('d.vigencia')
            ->whereDate('d.vigencia', '<=', Carbon::today()->addDays(30)->toDateString())
            ->orderBy('d.vigencia')
            ->limit(20)
            ->get(['p.id as proveedor_id', 'p.nombre as proveedor', 'd.nombre', 'd.vigencia']);
    }

    private function alertasContratos()
    {
        return DB::table('tblprovedores')
            ->whereNotNull('contrato_fin')
            ->whereDate('contrato_fin', '<=', Carbon::today()->addDays(30)->toDateString())
            ->orderBy('contrato_fin')
            ->limit(20)
            ->get(['id', 'nombre', 'contrato_fin']);
    }

    private function guardarDocumentosYAcciones(Request $request, int $proveedorId): void
    {
        $eliminar = array_filter(array_map('intval', (array) $request->input('documentos_eliminar', [])));
        if ($eliminar) {
            $viejos = DB::table('tblproveedor_documentos')
                ->where('proveedor_id', $proveedorId)
                ->whereIn('id', $eliminar)
                ->get();
            foreach ($viejos as $viejo) {
                if (! empty($viejo->archivo)) {
                    $ruta = public_path($viejo->archivo);
                    if (is_file($ruta)) {
                        @unlink($ruta);
                    }
                }
            }
            DB::table('tblproveedor_documentos')
                ->where('proveedor_id', $proveedorId)
                ->whereIn('id', $eliminar)
                ->delete();
        }

        $nombreDoc = trim((string) $request->input('documento_nombre', ''));
        if ($nombreDoc !== '') {
            $archivo = null;
            if ($request->hasFile('documento_archivo') && $request->file('documento_archivo')->isValid()) {
                $archivo = $this->guardarArchivoProveedor($request->file('documento_archivo'), $proveedorId, 'documento');
            }
            DB::table('tblproveedor_documentos')->insert([
                'proveedor_id' => $proveedorId,
                'nombre' => mb_substr($nombreDoc, 0, 150),
                'vigencia' => $request->filled('documento_vigencia') ? $request->input('documento_vigencia') : null,
                'archivo' => $archivo,
            ]);
        }

        $cerrar = array_filter(array_map('intval', (array) $request->input('acciones_cerrar', [])));
        if ($cerrar) {
            DB::table('tblproveedor_acciones')
                ->where('proveedor_id', $proveedorId)
                ->whereIn('id', $cerrar)
                ->update(['cerrada' => 1]);
        }

        $descripcion = trim((string) $request->input('accion_descripcion', ''));
        if ($descripcion !== '') {
            DB::table('tblproveedor_acciones')->insert([
                'proveedor_id' => $proveedorId,
                'descripcion' => mb_substr($descripcion, 0, 500),
                'fecha' => $request->filled('accion_fecha') ? $request->input('accion_fecha') : Carbon::today()->toDateString(),
                'cerrada' => 0,
            ]);
        }
    }

    private function guardarArchivoProveedor($archivo, int $id, string $prefijo): ?string
    {
        $ext = strtolower($archivo->getClientOriginalExtension() ?: '');
        if (! in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            return null;
        }

        $dir = public_path('archivos/proveedores/'.$id);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $nombre = $prefijo.'_'.date('YmdHis').'.'.$ext;
        $archivo->move($dir, $nombre);

        return 'archivos/proveedores/'.$id.'/'.$nombre;
    }

}
