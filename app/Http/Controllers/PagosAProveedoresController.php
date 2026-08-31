<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;



use App\Models\Proveedorproducto;
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
use Illuminate\Support\Arr;
use DB;
use Log;

class PagosAProveedoresController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function PagosIndex()
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varlistausers = $this->obtenerusuarios();
        $proveedores = DB::select("SELECT * FROM tblprovedores where estado = 'A';");

        return view('compras/PagosIndex', compact('varpantallas', 'varsubmenus', 'varlistausers', 'proveedores'));
    }
}
