<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
use App\Traits\DatosimpleTraits;
use App\Models\Clientes;
use App\Models\ClientesAtencion;
use App\Models\CondicionPago;

class ClientesController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;


    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $Clientes = $this->obtenerClientes();
            $Ciudades = $this->obtenerciudadesAll();
            $Monedas = \App\Models\Moneda::where('estatus', 'A')->orderBy('codigo')->get();
            $CondicionesPago = CondicionPago::where('estatus', 'A')->orderBy('dias_credito')->orderBy('nombre')->get();

            $ids = collect($Clientes)->pluck('id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
            $personasPorCliente = $ids->isEmpty()
                ? collect()
                : ClientesAtencion::whereIn('id_cliente', $ids)->orderBy('id')->get()->groupBy('id_cliente');

            $Clientes = collect($Clientes)->map(function ($cliente) use ($personasPorCliente) {
                $persona = optional($personasPorCliente->get((int) $cliente->id))->first();
                $cliente->nombre_atencion_display = $persona
                    ? strtoupper(trim((string) $persona->nombre_completo))
                    : ($cliente->nombre ?? '');
                return $cliente;
            });

            //ver_clientes
            $permisos1 = $this->forpermisos('insertar_clientes');
            $permisos2 = $this->forpermisos('editar_clientes');
            $permisos3 = $this->forpermisos('eliminar_clientes');
    

            return view('Clientes.index', compact('varpantallas', 'varsubmenus', 'permisos1', 'permisos2', 'permisos3',
             'Clientes','Ciudades','Monedas','CondicionesPago'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }


     public function insert(Request $request)
    {
        try {

            $Clientes = new Clientes();
            $Clientes->nombre = $request->get("nombre");
            $Clientes->alias = $request->get("alias");
            $Clientes->estado = "A";
            $Clientes->tipo = $request->get("tipo");
            $Clientes->razon_social = $request->get("razon_social");
            $Clientes->rfc= $request->get("rfc");
            $Clientes->telefono = $request->get("telefono");
            $Clientes->correo_electronico = $request->get("correo_electronico");
            $Clientes->id_ciudad  = $request->get("id_ciudad");
            $Clientes->colonia = $request->get("colonia");
            $Clientes->calle = $request->get("calle");
            $Clientes->numero_int = $request->get("numero_int");
            $Clientes->numero_ext = $request->get("numero_ext");
            $Clientes->cp = $request->get("cp");
            $Clientes->moneda_id = $request->get("moneda_id") ?: null;
            $Clientes->condicion_pago_id = $request->get("condicion_pago_id") ?: null;
            $Clientes->created_by = auth()->user()->name;

            if ($Clientes->save()) {
                return back()->with("success", "Cliente guardado correctamente");
            }

            return back()->with("warning", "No se pudo guardar el cliente")->withInput();

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()
                ->with("warningBD", "Error al guardar: " . $ex->getMessage())
                ->withInput();
        }
    }


     public function edit(int $id,Request $request)
    {
        try {
            $Clientes = Clientes::find($id);
            $Clientes->nombre = $request->get("nombre");
            $Clientes->alias = $request->get("alias");
            $Clientes->estado = $request->get("estado");
            $Clientes->tipo = $request->get("tipo");
            $Clientes->razon_social = $request->get("razon_social");
            $Clientes->rfc= $request->get("rfc");
            $Clientes->telefono = $request->get("telefono");
            $Clientes->correo_electronico = $request->get("correo_electronico");
            $Clientes->id_ciudad  = $request->get("id_ciudad");
            $Clientes->colonia = $request->get("colonia");
            $Clientes->calle = $request->get("calle");
            $Clientes->numero_int = $request->get("numero_int");
            $Clientes->numero_ext = $request->get("numero_ext");
            $Clientes->cp = $request->get("cp");
            $Clientes->moneda_id = $request->get("moneda_id") ?: null;
            $Clientes->condicion_pago_id = $request->get("condicion_pago_id") ?: null;
            $Clientes->updated_by = auth()->user()->name;

            if ($Clientes->save()) {
                return back()->with("success", "Cliente actualizado correctamente");
            }

            return back()->with("warning", "No se pudo actualizar el cliente")->withInput();

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()
                ->with("warningBD", "Error al actualizar: " . $ex->getMessage())
                ->withInput();
        }
    }


     public function delete(int $id,Request $request)
    {
        try {
            
            $Borrartbl1 = DB::select('delete from tblclientes where id = ? ', [$id]);
            return back()->with("success", "guardado correctamente");

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function getPersonasAtencion($id_cliente)
    {
        try {
            $personas = ClientesAtencion::where('id_cliente', $id_cliente)->get();
            return response()->json($personas);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Error al obtener personas de atención'], 500);
        }
    }

    public function insertPersonaAtencion(Request $request)
    {
        try {
            $persona = new ClientesAtencion();
            $persona->id_cliente = $request->get("id_cliente");
            $persona->primer_nombre = $request->get("primer_nombre");
            $persona->segundo_nombre = $request->get("segundo_nombre");
            $persona->apellido_paterno = $request->get("apellido_paterno");
            $persona->apellido_materno = $request->get("apellido_materno");
            $persona->telefono = $request->get("telefono");
            $persona->correo = $request->get("correo");

            if ($persona->save()) {
                return back()->with("success", "Persona de atención agregada correctamente");
            } else {
                return back()->with("warning", "No se pudo agregar la persona de atención");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "Error al agregar persona de atención");
        }
    }

    public function deletePersonaAtencion($id)
    {
        try {
            $personaAtencion = ClientesAtencion::findOrFail($id);
            $personaAtencion->delete();

            return response()->json(['success' => true, 'message' => 'Persona de atención eliminada correctamente']);
            
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Error al eliminar persona de atención'], 500);
        }
    }

    public function actualizarPersonaAtencion(Request $request, $id)
    {
        try {
            $request->validate([
                'primer_nombre' => 'required|string|max:50',
                'segundo_nombre' => 'nullable|string|max:50',
                'apellido_paterno' => 'required|string|max:50',
                'apellido_materno' => 'nullable|string|max:50',
                'telefono' => 'required|string|max:10',
                'correo' => 'required|email|max:100',
            ]);

            $personaAtencion = ClientesAtencion::findOrFail($id);
            
            $personaAtencion->primer_nombre = strtoupper($request->primer_nombre);
            $personaAtencion->segundo_nombre = $request->segundo_nombre ? strtoupper($request->segundo_nombre) : null;
            $personaAtencion->apellido_paterno = strtoupper($request->apellido_paterno);
            $personaAtencion->apellido_materno = $request->apellido_materno ? strtoupper($request->apellido_materno) : null;
            $personaAtencion->telefono = $request->telefono;
            $personaAtencion->correo = $request->correo;
            
            $personaAtencion->save();

            return response()->json(['success' => true, 'message' => 'Persona de atención actualizada correctamente']);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Error de validación', 'errors' => $e->errors()], 422);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar persona de atención: ' . $ex->getMessage()], 500);
        }
    }
}
