<?php
namespace App\Traits;
use App\Models\Alumno;
use App\Models\Puestos;
use App\Models\Sucursales;
use App\Models\Ciudades;
use App\Models\Empresas;
use App\Models\Empleados;
use App\Models\bancos;
use App\Models\tipo_descuento_infonavit;
use App\Models\Nominas_pagosenc;
use App\Models\Nominas_pagosdet;
use App\Models\tarifas_subsidio;
use App\Models\Vistas;
use App\Models\plazos;
use App\Models\Acciones;
use App\Models\User;
use App\Models\Departamentos;
use App\Models\empleados_bajas;
use App\Models\creditosempleadodet;
use App\Models\tasas;
use App\Models\cuentas;
use App\Models\Cajas;
use App\Models\Dias;
use App\Models\Horarios;
use DB;
use Illuminate\Support\Facades\Request;
use Log;
use Carbon\Carbon;

trait DatosimpleTraits
{
    public function obtenerusuarios()
    {
        $var = DB::select("select users.id,
        users.idempleado,
        users.tipo,
        users.id_tipo,
        users.id_empresa as id_empresa_user,
        tblsucursales.nombre as sucursal,
        tblsucursales.id as idsucursal,
        COALESCE(users.id_empresa, tblsucursales.idempresa) as id_empresa,
        COALESCE(emp_user.nombre_empresa, emp_suc.nombre_empresa) as empresa,
        users.name,
        users.estado_user,
        tblpuestos.nombre as puesto,
        CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS Nombre
        FROM users
        left join tblempleados on users.idempleado = tblempleados.id
        left join tblpuestos on tblempleados.idpuesto = tblpuestos.id
        left join tblsucursales on tblempleados.idsucursal = tblsucursales.id
        left join tblempresas emp_suc on emp_suc.id = tblsucursales.idempresa
        left join tblempresas emp_user on emp_user.id = users.id_empresa;");
        return collect($var);
    }

    public function obtenerUsuarioporIdempleado(int $idempleado)
    {
        $varuser = User::join('tblempleados', 'users.idempleado', '=', 'tblempleados.id')
            ->join('tblpuestos', 'tblempleados.idpuesto', '=', 'tblpuestos.id')
            ->join('tblsucursales', 'tblempleados.idsucursal', '=', 'tblsucursales.id')
            ->select(
                'users.id',
                'users.idempleado',
                'tblsucursales.nombre as sucursal',
                'tblsucursales.id as idsucursal',
                'users.name',
                'users.estado_user',
                'tblpuestos.nombre as puesto',
                DB::raw('CONCAT(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) AS Nombre')
            )
            ->where('users.idempleado', '=', $idempleado)
            ->first();
        return $varuser;
    }

    public function obtenerAlumnoPortal(int $id): ?Alumno
    {
        return Alumno::query()
            ->with([
                'escuela:id,nombre',
                'especialidad:id,nombre_especialidad',
                'empresa:id,nombre',
            ])
            ->find($id);
    }

    public function obtenerAlumnoPortalIdDesdeUsuario(?User $usuario, ?int $idFallback = null): ?int
    {
        if ($usuario && strtolower((string) ($usuario->tipo ?? '')) === 'alumno' && (int) ($usuario->id_tipo ?? 0) > 0) {
            return (int) $usuario->id_tipo;
        }

        return $idFallback && $idFallback > 0 ? $idFallback : null;
    }

    public function obtenerUsuariosCaja()
    {
        $varuser = Empleados::query()
            ->leftJoin('users', 'users.idempleado', '=', 'tblempleados.id')
            ->leftJoin('tblpuestos', 'tblempleados.idpuesto', '=', 'tblpuestos.id')
            ->leftJoin('tblsucursales', 'tblempleados.idsucursal', '=', 'tblsucursales.id')
            ->select(
                'tblempleados.id as id_empleado',
                'users.id',
                'users.idempleado',
                'users.name',
                'users.estado_user',
                'tblsucursales.nombre as sucursal',
                'tblsucursales.id as idsucursal',
                'tblpuestos.nombre as puesto',
                DB::raw('TRIM(CONCAT(COALESCE(tblempleados.primer_nombre,"")," ",COALESCE(tblempleados.segundo_nombre,"")," ",COALESCE(tblempleados.apellido_paterno,"")," ",COALESCE(tblempleados.apellido_materno,""))) AS Nombre')
            )
            ->where('tblempleados.estado', 'A')
            ->orderBy('Nombre')
            ->get();

        return $varuser;
    }

    public function resolverUsuarioResponsableCaja(int $idEmpleado): User
    {
        $user = User::where('idempleado', $idEmpleado)->first();
        if ($user) {
            return $user;
        }

        $empleado = Empleados::find($idEmpleado);
        if (!$empleado) {
            throw new \InvalidArgumentException('El empleado seleccionado no existe.');
        }

        if ($empleado->estado !== 'A') {
            throw new \InvalidArgumentException('El empleado seleccionado no está activo.');
        }

        $nombreUsuario = $this->generarNombreUsuarioCaja($empleado, $idEmpleado);
        $email = !empty($empleado->correo)
            ? trim($empleado->correo)
            : ('empleado.' . $idEmpleado . '@iohisa.local');

        $contador = 1;
        $emailBase = $email;
        while (User::where('email', $email)->exists()) {
            $email = str_replace('@', '.' . $contador . '@', $emailBase);
            $contador++;
        }

        $contadorNombre = 1;
        $nombreBase = $nombreUsuario;
        while (User::where('name', $nombreUsuario)->exists()) {
            $nombreUsuario = $nombreBase . $contadorNombre;
            $contadorNombre++;
        }

        $user = new User();
        $user->name = $nombreUsuario;
        $user->email = $email;
        $user->password = bcrypt(bin2hex(random_bytes(8)));
        $user->idempleado = $idEmpleado;
        $user->id_tipo = $idEmpleado;
        $user->tipo = 'empleado';
        $user->estado_user = 'A';
        $user->nombre_foto = $empleado->nombre_foto ?: '0.png';
        $user->created_by = auth()->user()->name ?? 'sistema';
        $user->save();

        return $user;
    }

    protected function generarNombreUsuarioCaja(Empleados $empleado, int $idEmpleado): string
    {
        $base = strtolower(preg_replace(
            '/[^a-z0-9]/',
            '',
            ($empleado->primer_nombre ?? '') . ($empleado->apellido_paterno ?? '')
        ));

        return $base !== '' ? $base : ('empleado' . $idEmpleado);
    }

    public function obtenercuentasPrincipales(int $idCuenta)
    {
        $odps = cuentas::select('tblcuentas.id', 'tblcuentas.nombre as descripcion', 'tblcuentas.saldo_actual', 'tblcuentas.saldo_inicial', 'tblcuentas.tipo', 'tblcuentas.fecha_alta', 'tblcuentas.status')
            ->where('tblcuentas.id', '=', $idCuenta)
            ->get();
        return $odps;
    }

    public function obtenervistas()
    {
        $varvista = Vistas::join('tbldepartamentos', 'tbldepartamentos.id', '=', 'tblvistas.iddepartamento')
            ->select('tblvistas.id', 'tblvistas.nombre', 'tbldepartamentos.nombre as departamento')
            ->orderBy('tblvistas.id', 'desc')
            ->get();
        return $varvista;
    }

    public function razonSocialxNombre(string $nombre)
    {
        $condiciones = [
            ['tblempresas.nombre_empresa', '=', $nombre]
        ];

        $val = Empresas::select(
            'tblempresas.nombre_empresa as razon_social',
            'tblempresas.descripcion as empresa',
            'tblempresas.representada',
            'tblempresas.direccion_fiscal',
            'tblempresas.icono',
            'tblempresas.marca_agua'
        )
            ->where($condiciones)
            ->get();
        return $val;
    }

    public function obtenerAccionesUser(int $id)
    {
        $var = DB::select("SELECT tblusuario_acciones.id, users.id as id_user, users.name, 
                CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmpleado,
                tblpuestos.nombre as puesto,
                tblacciones.id as id_accion,
                tblacciones.descripcion_accion, 
                tblacciones.nombre_accion, 
                tblvistas.descripcion as descripcion_vista, 
                tblvistas.nombre as nombre_vista,
                tbldepartamentos.descripcion as descripcion_departamento, 
                tbldepartamentos.nombre as nombre_departamento,
                tblusuario_acciones.created_at,
                tblusuario_acciones.updated_at, 
                tblusuario_acciones.created_by, 
                tblusuario_acciones.updated_by
            FROM users
            INNER JOIN tblempleados on users.idempleado = tblempleados.id 
            INNER JOIN tblpuestos on tblpuestos.id = tblempleados.idpuesto 
            INNER JOIN tblusuario_acciones on users.id = tblusuario_acciones.idusuario 
            INNER JOIN tblacciones on tblusuario_acciones.idacciones = tblacciones.id 
            INNER JOIN tblvistas on tblacciones.idvista = tblvistas.id 
            INNER JOIN tbldepartamentos on tblvistas.iddepartamento = tbldepartamentos.id 
            WHERE users.id = ? 
            ORDER BY tblacciones.id desc;",
            [$id]
        );
        return collect($var);
    }

    public function obtenercuentasActivas()
    {
        $odps = cuentas::select(
            'tblcuentas.id',
            'tblcuentas.id_cuenta',
            'tblcuentas.saldo_inicial',
            'tblcuentas.saldo_actual',
            'tblcuentas.status',
            'tblcuentas.nombre as descripcion',
            'tblcuentas.created_at',
            'tblcuentas.updated_at',
            'tblcuentas.created_by',
            'tblcuentas.updated_by'
        )
            ->where('tblcuentas.status', '=', 'A')
            ->get();
        return $odps;
    }
    public function obtenercuentasActivasxid(int $id){
        $odps = cuentas::select('tblcuentas.id',
            'tblcuentas.id_cuenta',
            'tblcuentas.saldo_inicial',
            'tblcuentas.saldo_actual',
            'tblcuentas.status',
            'tblcuentas.nombre as descripcion',
            'tblcuentas.created_at',
            'tblcuentas.updated_at',
            'tblcuentas.created_by',
            'tblcuentas.updated_by')
        ->where('tblcuentas.status','=', 'A')
        ->where('tblcuentas.id','=', $id)
        ->first();
        return $odps;
    }

    public function obtenerultimasucusal()
    {
        $var = DB::select('SELECT consecutivo FROM tblsucursales ORDER BY id desc limit 1;');
        return collect($var);
    }

    public function obtenerCuentasBanco()
    {
        $varultimoempleado = DB::select("SELECT tblcuentas.id, 
                tblcuentas.id_cuenta,
                tblcuentas.nombre as descripcion, 
                tblcuentas.saldo_inicial, 
                tblcuentas.saldo_actual, 
                tblcuentas.id_empresa,
                tblempresas.nombre_empresa,
                tblcuentas.status,
                tblcuentas.tipo,
                tblcuentas.fecha_alta,
                tblcuentas.created_at,
                tblcuentas.updated_at, 
                tblcuentas.created_by, 
                tblcuentas.updated_by
            FROM tblcuentas
            INNER JOIN tblempresas on tblcuentas.id_empresa = tblempresas.id 
            ORDER BY tblcuentas.id desc;"
        );
        return collect($varultimoempleado);
    }

    public function obtenerExportCajasAll()
    {
        $varhistcuent = DB::select("SELECT 
                tblcajas.id, 
                tblcajas.nombre,
                tblcajas.status,
                tblcajas.tipo,
                tblcajas.saldo_inicial,
                tblcajas.saldo_actual,
                tblcajas.fecha_alta,
                tblcajas.id_sucursal,
                suc.nombre as nombre_sucursal,
                users.name,
                tblempleados.id as idempleado,
                CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreResponsable,
                tblpuestos.nombre as puesto,
                sucEmp.nombre as sucursalEmpleado,
                tblempresas.nombre_empresa,
                tblcajas.created_at,
                tblcajas.updated_at,
                tblcajas.created_by,
                tblcajas.updated_by  
            FROM tblcajas 
            INNER JOIN tblsucursales suc on suc.id = tblcajas.id_sucursal
            INNER JOIN users on users.id = tblcajas.id_usuario
            INNER JOIN tblempleados on tblempleados.id = users.idempleado
            INNER JOIN tblpuestos on tblpuestos.id = tblempleados.idpuesto
            INNER JOIN tblsucursales sucEmp on sucEmp.id = tblempleados.idsucursal
            INNER JOIN tblnominas on tblnominas.idempleado = tblempleados.id
            INNER JOIN tblempresas on tblempresas.id = tblnominas.idempresa 
            ORDER BY tblcajas.id desc;"
        );
        return collect($varhistcuent);
    }

    public function obtenerCajasAll()
    {
        $varhistcuent = DB::select("SELECT 
                tblcajas.id, 
                tblcajas.nombre,
                tblcajas.status,
                tblcajas.tipo,
                tblcajas.saldo_inicial,
                tblcajas.saldo_actual,
                DATE_FORMAT(tblcajas.fecha_alta, '%d/%m/%Y') as fecha_alta,
                tblcajas.responsable_status,
                tblcajas.id_sucursal,
                suc.nombre as nombre_sucursal,
                tblcajas.id_usuario,
                users.name,
                CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreResponsable,
                tblempleados.id as idempleado,
                tblempleados.nombre_foto,
                tblpuestos.nombre as puesto,
                sucEmp.nombre as sucursalEmpleado,
                tblempresas.nombre_empresa,
                tblcajas.ruta_comprobante_carta,
                tblcajas.status_carta,
                tblcajas.ruta_comprobante_pagare,
                tblcajas.status_pagare,
                tblcajas.created_at,
                tblcajas.updated_at,
                tblcajas.created_by,
                tblcajas.updated_by  
            FROM tblcajas 
            INNER JOIN tblsucursales suc on suc.id = tblcajas.id_sucursal
            INNER JOIN users on users.id = tblcajas.id_usuario
            INNER JOIN tblempleados on tblempleados.id = users.idempleado
            INNER JOIN tblpuestos on tblpuestos.id = tblempleados.idpuesto
            INNER JOIN tblsucursales sucEmp on sucEmp.id = tblempleados.idsucursal
            INNER JOIN tblnominas on tblnominas.idempleado = tblempleados.id
            INNER JOIN tblempresas on tblempresas.id = tblnominas.idempresa 
            ORDER BY tblcajas.id desc;");
        return collect($varhistcuent);
    }

    public function obtenerCajas()
    {
        $varhistcuent = DB::select("SELECT 
                tblcajas.id, 
                tblcajas.nombre,
                tblcajas.status,
                tblcajas.tipo,
                tblcajas.saldo_inicial,
                tblcajas.saldo_actual,
                tblcajas.fecha_alta,
                tblcajas.responsable_status,
                tblcajas.id_sucursal,
                suc.nombre as nombre_sucursal,
                tblcajas.id_usuario,
                users.name,
                CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreResponsable,
                tblempleados.id as idempleado,
                tblempleados.nombre_foto,
                tblpuestos.nombre as puesto,
                sucEmp.nombre as sucursalEmpleado,
                tblempresas.nombre_empresa,
                tblcajas.ruta_comprobante_carta,
                tblcajas.status_carta,
                tblcajas.ruta_comprobante_pagare,
                tblcajas.status_pagare,
                tblcajas.created_at,
                tblcajas.updated_at,
                tblcajas.created_by,
                tblcajas.updated_by,
        tblcajas.id as id_caj,
        (select tblarqueocajas.estado from tblarqueocajas where tblarqueocajas.id_caja = id_caj and tblarqueocajas.estado <> 'Cancelado' order by id desc limit 1) as arqueo  
            FROM tblcajas 
            INNER JOIN tblsucursales suc on suc.id = tblcajas.id_sucursal
            INNER JOIN users on users.id = tblcajas.id_usuario
            INNER JOIN tblempleados on tblempleados.id = users.idempleado
            INNER JOIN tblpuestos on tblpuestos.id = tblempleados.idpuesto
            INNER JOIN tblsucursales sucEmp on sucEmp.id = tblempleados.idsucursal
            INNER JOIN tblnominas on tblnominas.idempleado = tblempleados.id
            INNER JOIN tblempresas on tblempresas.id = tblnominas.idempresa
            WHERE tblcajas.status = 'A';");
        return collect($varhistcuent);
    }

    public function obtenerCajasActivas()
    {
        $varhistcuent = DB::select("SELECT 
                tblcajas.id, 
                tblcajas.nombre,
                tblcajas.status,
                tblcajas.tipo,
                tblcajas.saldo_inicial,
                tblcajas.saldo_actual,
                tblcajas.fecha_alta,
                tblcajas.responsable_status,
                tblcajas.id_sucursal,
                suc.nombre as nombre_sucursal,
                tblcajas.id_usuario,
                users.name,
                CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreResponsable,
                tblempleados.id as idempleado,
                tblempleados.nombre_foto,
                tblpuestos.nombre as puesto,
                sucEmp.nombre as sucursalEmpleado,
                tblempresas.nombre_empresa,
                tblcajas.ruta_comprobante_carta,
                tblcajas.status_carta,
                tblcajas.ruta_comprobante_pagare,
                tblcajas.status_pagare,
                tblcajas.created_at,
                tblcajas.updated_at,
                tblcajas.created_by,
                tblcajas.updated_by,
        tblcajas.id as id_caj,
        (select tblarqueocajas.estado from tblarqueocajas where tblarqueocajas.id_caja = id_caj and tblarqueocajas.estado <> 'Cancelado' order by id desc limit 1) as arqueo   
            FROM tblcajas 
            INNER JOIN tblsucursales suc on suc.id = tblcajas.id_sucursal
            INNER JOIN users on users.id = tblcajas.id_usuario
            INNER JOIN tblempleados on tblempleados.id = users.idempleado
            INNER JOIN tblpuestos on tblpuestos.id = tblempleados.idpuesto
            INNER JOIN tblsucursales sucEmp on sucEmp.id = tblempleados.idsucursal
            INNER JOIN tblnominas on tblnominas.idempleado = tblempleados.id
            INNER JOIN tblempresas on tblempresas.id = tblnominas.idempresa
            WHERE tblcajas.status = 'A' and tblcajas.tipo = 'caja';");
        return collect($varhistcuent);
    }

    public function obtenerCajasxId(int $id)
    {
        $varhistcuent = DB::select("SELECT 
                tblcajas.id, 
                tblcajas.nombre,
                tblcajas.status,
                tblcajas.tipo,
                tblcajas.saldo_inicial,
                tblcajas.saldo_actual,
                tblcajas.fecha_alta,
                tblcajas.responsable_status,
                tblcajas.id_sucursal,        
                suc.nombre as nombre_sucursal,
                tblcajas.id_usuario,
                users.name,
                CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreResponsable,
                tblempleados.id as idempleado,
                tblempleados.nombre_foto,
                tblpuestos.nombre as puesto,
                sucEmp.nombre as sucursalEmpleado,
                tblempresas.id as idempresa,
                tblempresas.nombre_empresa,
                tblcajas.ruta_comprobante_carta,
                tblcajas.status_carta,
                tblcajas.ruta_comprobante_pagare,
                tblcajas.status_pagare,
                tblcajas.created_at,
                tblcajas.updated_at,
                tblcajas.created_by,
                tblcajas.updated_by  
            FROM tblcajas 
            INNER JOIN tblsucursales suc on suc.id = tblcajas.id_sucursal
            INNER JOIN tblempresas on tblempresas.id = suc.idempresa 
            INNER JOIN users on users.id = tblcajas.id_usuario
            INNER JOIN tblempleados on tblempleados.id = users.idempleado
            INNER JOIN tblpuestos on tblpuestos.id = tblempleados.idpuesto
            INNER JOIN tblsucursales sucEmp on sucEmp.id = tblempleados.idsucursal
            WHERE tblcajas.id = ?;", [$id]);
        return collect($varhistcuent);
    }

    public function obtenerCajasActivasxid(int $id){
        $odps = Cajas::select('tblcajas.id',
            'tblcajas.saldo_inicial',
            'tblcajas.saldo_actual',
            'tblcajas.status',
            'tblcajas.nombre as descripcion',
            'tblcajas.created_at',
            'tblcajas.updated_at',
            'tblcajas.created_by',
            'tblcajas.updated_by')
        ->where('tblcajas.status','=', 'A')
        ->where('tblcajas.id','=', $id)
        ->first();
        return $odps;

    }

    public function obtenersaldocuenta(int $idcuenta)
    {
        $varsaldo = cuentas::select('tblcuentas.saldo_actual')
        ->where('tblcuentas.id','=',$idcuenta)
        ->get();
        return $varsaldo;

    }

    public function obtenerGastos()
    {
        $varhistcuent = DB::select("SELECT 
                tblgastos.id, 
                tblgastos.nombre ,
                tblgastos.iva ,
                tblgastos.ret_iva ,
                tblgastos.ret_isr ,
                tblgastos.ret_isr_resico ,
                tblgastos.estado ,
                tblgastos.tipo ,
                tblgastos.rubro ,
                tblgastos.id_empresa  ,
                tblempresas.nombre_empresa  ,
                tblgastos.created_at ,
                tblgastos.updated_at ,
                tblgastos.created_by ,
                tblgastos.updated_by  
            FROM tblgastos 
            INNER JOIN tblempresas on tblempresas.id = tblgastos.id_empresa 
            ORDER BY id desc;");
        return collect($varhistcuent);
    }


    public function obtenerGastosxId(int $id)
    {
        $varhistcuent = DB::select("SELECT * FROM tblgastos WHERE tblgastos.id = ? ORDER BY id desc;", [$id]);
        return collect($varhistcuent);
    }

    public function obtenerGastosNumxEmpresa(int $idEmpresa)
    {
        $varhistcuent = DB::select("SELECT * FROM tblgastos WHERE tblgastos.id_empresa = ? ORDER BY tblgastos.id desc limit 1;", [$idEmpresa]);
        return collect($varhistcuent);
    }

    public function obtenerGastosxEmpresa(int $idEmpresa)
    {
        $varhistcuent = DB::select("SELECT * FROM tblgastos WHERE tblgastos.id_empresa = ? or tblgastos.id_empresa = 0 ORDER BY tblgastos.id desc;", [$idEmpresa]);
        return collect($varhistcuent);
    }

    public function obtenerGastosExcel()
    {
        $var = DB::select("SELECT 
                tblgastos.id, 
                CASE 
                    WHEN tblgastos.estado = 'A' THEN 'ACTIVO'
                    WHEN tblgastos.estado = 'I' THEN 'INACTIVO'
                END as estado,
                tblgastos.nombre,
                CONCAT('% ',tblgastos.iva) AS iva,
                CONCAT('% ',tblgastos.ret_iva) AS ret_iva,
                CONCAT('% ',tblgastos.ret_isr) AS ret_isr,
                CONCAT('% ',tblgastos.ret_isr_resico) AS ret_isr_resico,
                tblgastos.rubro,
                CASE 
                    WHEN tblgastos.tipo = 'B' THEN 'BANCO'
                    WHEN tblgastos.tipo = 'E' THEN 'EFECTIVO'
                END as tipo,
                tblempresas.nombre_empresa ,
                CONCAT(tblgastos.created_at,' - ',tblgastos.created_by) AS creado,
                CONCAT(tblgastos.updated_at,' - ',tblgastos.updated_by) AS actualizado
            FROM tblgastos 
            INNER JOIN tblempresas on tblempresas.id = tblgastos.id_empresa 
            ORDER BY tblgastos.id asc;");
        return collect($var);
    }

    public function obtenerGastosxEmpresaExcel(int $idEmpresa)
    {
        $var = DB::select("SELECT 
                tblgastos.id_consecutivo, 
                CASE 
                    WHEN tblgastos.estado = 'A' THEN 'ACTIVO'
                    WHEN tblgastos.estado = 'I' THEN 'INACTIVO'
                END as estado,
                tblgastos.nombre,
                CONCAT('% ',tblgastos.iva) AS iva,
                CONCAT('% ',tblgastos.ret_iva) AS ret_iva,
                CONCAT('% ',tblgastos.ret_isr) AS ret_isr,
                CONCAT('% ',tblgastos.ret_isr_resico) AS ret_isr_resico,
                tblgastos.rubro,
                CASE 
                    WHEN tblgastos.tipo = 'B' THEN 'BANCO'
                    WHEN tblgastos.tipo = 'E' THEN 'EFECTIVO'
                END as tipo,
                tblempresas.nombre_empresa,
                CONCAT(tblgastos.created_at,' - ',tblgastos.created_by) AS creado,
                CONCAT(tblgastos.updated_at,' - ',tblgastos.updated_by) AS actualizado
            FROM tblgastos 
            INNER JOIN tblempresas on tblempresas.id = tblgastos.id_empresa 
            WHERE tblgastos.id_empresa = ? 
            ORDER BY tblgastos.id_consecutivo asc;", [$idEmpresa]);
        return collect($var);
    }

    public function obtenerMovCuentas(int $id)
    {
        $varhistcuent = DB::select("SELECT 
                tblmovimientos_cuentas.id,
                tblmovimientos_cuentas.id_cuenta as id_tipo,
                tblcuentas.nombre as cuenta,
                tblmovimientos_cuentas.estado,
                tblmovimientos_cuentas.id_empleado, 
                CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmp,
                tblempresas.nombre_empresa as pertenencia, 
                tblempresas.nombre_empresa as empresa, 
                tblempresas.id as idempresa,
                tblmovimientos_cuentas.tipo_movimiento, 
                tblmovimientos_cuentas.concepto,
                tblmovimientos_cuentas.descripcion,
                tblmovimientos_cuentas.responsable,
                tblmovimientos_cuentas.total_iva ,
                tblmovimientos_cuentas.total_ret_iva ,
                tblmovimientos_cuentas.total_ret_isr ,
                tblmovimientos_cuentas.total_ret_isr_resico ,
                tblmovimientos_cuentas.ingreso, 
                tblmovimientos_cuentas.egreso, 
                tblmovimientos_cuentas.saldo, 
                tblmovimientos_cuentas.numero_referencia,
                tblmovimientos_cuentas.tipo_referencia, 
                tblmovimientos_cuentas.numero_poliza, 
                tblmovimientos_cuentas.fecha,
                tblmovimientos_cuentas.ruta_evidencia,
                tblmovimientos_cuentas.created_at,
                tblmovimientos_cuentas.updated_at, 
                tblmovimientos_cuentas.created_by, 
                tblmovimientos_cuentas.updated_by
            FROM tblmovimientos_cuentas
            INNER JOIN tblcuentas on tblcuentas.id = tblmovimientos_cuentas.id_cuenta 
            left JOIN tblempleados on tblempleados.id = tblmovimientos_cuentas.id_empleado 
            INNER JOIN tblempresas on tblempresas.id = tblcuentas.id_empresa 
            WHERE tblmovimientos_cuentas.id = ?
            ORDER BY tblmovimientos_cuentas.id asc;", [$id]);
        return collect($varhistcuent);
    }

    public function obtenerMovCajas(int $id)
    {
        $varhistcaj = DB::select("SELECT 
                tblmovimientos_cajas.id,
                tblmovimientos_cajas.id_caja as id_tipo,
                tblcajas.nombre as cuenta,
                tblmovimientos_cajas.estado,
                tblmovimientos_cajas.id_empleado, 
                CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmp,
                tblsucursales.nombre as pertenencia, 
                tblempresas.id as idempresa,
                tblempresas.nombre_empresa as empresa,
                tblmovimientos_cajas.tipo_movimiento, 
                tblmovimientos_cajas.concepto,
                tblmovimientos_cajas.descripcion,
                tblmovimientos_cajas.responsable,
                tblmovimientos_cajas.total_iva ,
                tblmovimientos_cajas.total_ret_iva ,
                tblmovimientos_cajas.total_ret_isr ,
                tblmovimientos_cajas.total_ret_isr_resico ,
                tblmovimientos_cajas.ingreso, 
                tblmovimientos_cajas.egreso, 
                tblmovimientos_cajas.saldo, 
                tblmovimientos_cajas.ruta_evidencia, 
                tblmovimientos_cajas.numero_referencia,
                tblmovimientos_cajas.tipo_referencia, 
                tblmovimientos_cajas.numero_poliza, 
                tblmovimientos_cajas.fecha,
                tblmovimientos_cajas.created_at,
                tblmovimientos_cajas.updated_at, 
                tblmovimientos_cajas.created_by, 
                tblmovimientos_cajas.updated_by
            FROM tblmovimientos_cajas
            INNER JOIN tblcajas on tblcajas.id = tblmovimientos_cajas.id_caja 
            INNER JOIN tblempleados on tblempleados.id = tblmovimientos_cajas.id_empleado 
            INNER JOIN tblsucursales on tblsucursales.id = tblcajas.id_sucursal 
            INNER JOIN tblempresas on tblempresas.id = tblsucursales.idempresa 
            WHERE tblmovimientos_cajas.id = ? 
            ORDER BY tblmovimientos_cajas.id asc;", [$id]);
        return collect($varhistcaj);
    }

    public function obtenerHistorialCuentas(int $id, string $fecha_inicio, string $fecha_fin, string $tipo_fecha = 'aplicacion', ?string $tipo_movimiento = null)
    {
        $fechaCampo = $tipo_fecha === 'movimiento'
            ? 'tblmovimientos_cuentas.created_at'
            : 'tblmovimientos_cuentas.fecha';

        $sql = 'select tblmovimientos_cuentas.id,
            tblmovimientos_cuentas.id_cuenta as id_tipo,
            tblcuentas.nombre as cuenta,
            tblmovimientos_cuentas.estado,
            tblmovimientos_cuentas.id_empleado,
            CONCAT(tblempleados.primer_nombre," ", tblempleados.segundo_nombre," ", tblempleados.apellido_paterno," ", tblempleados.apellido_materno) AS nombreEmp,
            tblempresas.nombre_empresa as pertenencia,
            tblempresas.nombre_empresa as empresa,
            tblempresas.id as idempresa,
            tblmovimientos_cuentas.tipo_movimiento,
            tblmovimientos_cuentas.concepto,
            tblmovimientos_cuentas.descripcion,
            tblmovimientos_cuentas.responsable,
            tblmovimientos_cuentas.total_iva,
            tblmovimientos_cuentas.total_ret_iva,
            tblmovimientos_cuentas.total_ret_isr,
            tblmovimientos_cuentas.total_ret_isr_resico,
            tblmovimientos_cuentas.ingreso,
            tblmovimientos_cuentas.egreso,
            tblmovimientos_cuentas.saldo,
            tblmovimientos_cuentas.numero_referencia,
            tblmovimientos_cuentas.tipo_referencia,
            tblmovimientos_cuentas.numero_poliza,
            tblmovimientos_cuentas.fecha,
            DATE_FORMAT(tblmovimientos_cuentas.fecha, "%Y-%m-%d %H:%i:%s") as fecha_orden,
            DATE_FORMAT(tblmovimientos_cuentas.fecha, "%d/%m/%Y") as fecha_aplicacion_formato,
            DATE_FORMAT(tblmovimientos_cuentas.fecha, "%H:%i:%s") as hora_aplicacion_formato,
            tblmovimientos_cuentas.ruta_evidencia,
            tblmovimientos_cuentas.created_at,
            DATE_FORMAT(tblmovimientos_cuentas.created_at, "%Y-%m-%d %H:%i:%s") as created_at_orden,
            DATE_FORMAT(tblmovimientos_cuentas.created_at, "%d/%m/%Y") as fecha_movimiento_formato,
            DATE_FORMAT(tblmovimientos_cuentas.created_at, "%H:%i:%s") as hora_movimiento_formato,
            tblmovimientos_cuentas.updated_at,
            tblmovimientos_cuentas.created_by,
            tblmovimientos_cuentas.updated_by from tblmovimientos_cuentas
            inner join tblcuentas on tblcuentas.id = tblmovimientos_cuentas.id_cuenta 
            left join tblempleados on tblempleados.id = tblmovimientos_cuentas.id_empleado 
            inner join tblempresas on tblempresas.id = tblcuentas.id_empresa 
            where tblmovimientos_cuentas.id_cuenta = ? and '.$fechaCampo.' between ? and ?';
        $params = [$id, $fecha_inicio, $fecha_fin];
        if (!is_null($tipo_movimiento) && $tipo_movimiento !== 'TODOS') {
            $sql .= ' and tblmovimientos_cuentas.tipo_movimiento = ?';
            $params[] = $tipo_movimiento;
        }
        $sql .= ' ORDER BY tblmovimientos_cuentas.created_at DESC;';

        $var = DB::select($sql, $params);
        return collect($var);
    }

    public function ExcelHistorialCuentas(int $id, string $fecha_inicio, string $fecha_fin, string $tipo_fecha = 'aplicacion', ?string $tipo_movimiento = null)
    {
        $fechaCampo = $tipo_fecha === 'movimiento'
            ? 'tblmovimientos_cuentas.created_at'
            : 'tblmovimientos_cuentas.fecha';

        $sql = "SELECT 
                tblmovimientos_cuentas.created_at,
                tblempresas.nombre_empresa,
                tblcuentas.nombre as cuenta, 
                tblmovimientos_cuentas.concepto,
                tblmovimientos_cuentas.responsable,
                CASE 
                    WHEN tblmovimientos_cuentas.ingreso = 0 THEN '  '
                    ELSE tblmovimientos_cuentas.ingreso 
                END AS ingreso,
                CASE 
                    WHEN tblmovimientos_cuentas.egreso = 0 THEN '  '
                    ELSE tblmovimientos_cuentas.egreso 
                END AS egresos,
                tblmovimientos_cuentas.saldo AS saldo,
                tblmovimientos_cuentas.numero_poliza, 
                tblmovimientos_cuentas.numero_referencia,
                CASE 
                    WHEN tblmovimientos_cuentas.estado = 'A' THEN 'AUTORIZADO'
                    WHEN tblmovimientos_cuentas.estado = 'D' THEN 'DECLINADO'
                    WHEN tblmovimientos_cuentas.estado = 'E' THEN 'EN ESPERA'
                    ELSE 'INCONCLUSO'
                END as estado,
                tblmovimientos_cuentas.created_by,
                CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmp
            FROM tblmovimientos_cuentas
            INNER JOIN tblcuentas on tblcuentas.id = tblmovimientos_cuentas.id_cuenta 
            LEFT JOIN tblempleados on tblempleados.id = tblmovimientos_cuentas.id_empleado 
            INNER JOIN tblempresas on tblempresas.id = tblcuentas.id_empresa
            WHERE tblmovimientos_cuentas.id_cuenta = ? and {$fechaCampo} >= ? and {$fechaCampo} <= ?";
        $params = [$id, $fecha_inicio, $fecha_fin];
        if (!is_null($tipo_movimiento) && $tipo_movimiento !== 'TODOS') {
            $sql .= " and tblmovimientos_cuentas.tipo_movimiento = ?";
            $params[] = $tipo_movimiento;
        }
        $sql .= " ORDER BY tblmovimientos_cuentas.id asc;";

        $varhistcuent = DB::select($sql, $params);
        return collect($varhistcuent);
    }

    public function obtenerHistorialCajas(int $id, string $fecha_inicio, string $fecha_fin, string $tipo_fecha = 'aplicacion', ?string $tipo_movimiento = null)
    {
        $fechaCampo = $tipo_fecha === 'movimiento'
            ? 'tblmovimientos_cajas.created_at'
            : 'tblmovimientos_cajas.fecha';

        $sql = "SELECT 
            tblmovimientos_cajas.id,
            tblmovimientos_cajas.id_caja as id_tipo,
            tblcajas.nombre as cuenta,
            tblmovimientos_cajas.estado,
            tblmovimientos_cajas.id_empleado, 
            CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmp,
            tblsucursales.nombre as pertenencia, 
            tblempresas.id as idempresa,
            tblempresas.nombre_empresa as empresa,
            tblmovimientos_cajas.tipo_movimiento, 
            tblmovimientos_cajas.concepto,
            tblmovimientos_cajas.descripcion,
            tblmovimientos_cajas.responsable,
            tblmovimientos_cajas.total_iva ,
            tblmovimientos_cajas.total_ret_iva ,
            tblmovimientos_cajas.total_ret_isr ,
            tblmovimientos_cajas.total_ret_isr_resico ,
            tblmovimientos_cajas.ingreso, 
            tblmovimientos_cajas.egreso, 
            tblmovimientos_cajas.saldo, 
            tblmovimientos_cajas.ruta_evidencia, 
            tblmovimientos_cajas.numero_referencia,
            tblmovimientos_cajas.tipo_referencia, 
            tblmovimientos_cajas.numero_poliza, 
            tblmovimientos_cajas.fecha,
            DATE_FORMAT(tblmovimientos_cajas.fecha, '%Y-%m-%d %H:%i:%s') as fecha_orden,
            DATE_FORMAT(tblmovimientos_cajas.fecha, '%d/%m/%Y') as fecha_aplicacion_formato,
            DATE_FORMAT(tblmovimientos_cajas.fecha, '%H:%i:%s') as hora_aplicacion_formato,
            tblmovimientos_cajas.created_at,
            DATE_FORMAT(tblmovimientos_cajas.created_at, '%Y-%m-%d %H:%i:%s') as created_at_orden,
            DATE_FORMAT(tblmovimientos_cajas.created_at, '%d/%m/%Y') as fecha_movimiento_formato,
            DATE_FORMAT(tblmovimientos_cajas.created_at, '%H:%i:%s') as hora_movimiento_formato,
            tblmovimientos_cajas.updated_at, 
            tblmovimientos_cajas.created_by, 
            tblmovimientos_cajas.updated_by
        FROM tblmovimientos_cajas
        INNER JOIN tblcajas on tblcajas.id = tblmovimientos_cajas.id_caja 
        LEFT JOIN tblempleados on tblempleados.id = tblmovimientos_cajas.id_empleado 
        INNER JOIN tblsucursales on tblsucursales.id = tblcajas.id_sucursal 
        INNER JOIN tblempresas on tblempresas.id = tblsucursales.idempresa 
        WHERE tblmovimientos_cajas.id_caja = ? and {$fechaCampo} >= ? and {$fechaCampo} <= ?";
        $params = [$id, $fecha_inicio, $fecha_fin];
        if (!is_null($tipo_movimiento) && $tipo_movimiento !== 'TODOS') {
            $sql .= " and tblmovimientos_cajas.tipo_movimiento = ?";
            $params[] = $tipo_movimiento;
        }
        $sql .= " ORDER BY tblmovimientos_cajas.created_at DESC;";

        $varhistcaj = DB::select($sql, $params);
        return collect($varhistcaj);
    }

    public function ExcelHistorialCajas(int $id, string $fecha_inicio, string $fecha_fin, string $tipo_fecha = 'aplicacion', ?string $tipo_movimiento = null)
    {
        $fechaCampo = $tipo_fecha === 'movimiento'
            ? 'tblmovimientos_cajas.created_at'
            : 'tblmovimientos_cajas.fecha';

        $sql = "SELECT 
                tblmovimientos_cajas.created_at,
                tblsucursales.nombre as sucursal,
                tblcajas.nombre as cuenta, 
                tblmovimientos_cajas.concepto,
                tblmovimientos_cajas.responsable,
                CASE 
                    WHEN tblmovimientos_cajas.ingreso = 0 THEN '  '
                    ELSE tblmovimientos_cajas.ingreso 
                END AS ingreso,
                CASE 
                    WHEN tblmovimientos_cajas.egreso = 0 THEN '  '
                    ELSE tblmovimientos_cajas.egreso 
                END AS egresos,
                tblmovimientos_cajas.saldo AS saldo,
                tblmovimientos_cajas.numero_poliza, 
                tblmovimientos_cajas.numero_referencia,
                CASE 
                    WHEN tblmovimientos_cajas.estado = 'A' THEN 'AUTORIZADO'
                    WHEN tblmovimientos_cajas.estado = 'D' THEN 'DECLINADO'
                    WHEN tblmovimientos_cajas.estado = 'E' THEN 'EN ESPERA'
                    ELSE 'INCONCLUSO'
                END as estado,
                tblmovimientos_cajas.created_by,
                CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmp
            FROM tblmovimientos_cajas
            INNER JOIN tblcajas on tblcajas.id = tblmovimientos_cajas.id_caja 
            LEFT JOIN tblempleados on tblempleados.id = tblmovimientos_cajas.id_empleado 
            INNER JOIN tblsucursales on tblsucursales.id = tblcajas.id_sucursal 
            WHERE tblmovimientos_cajas.id_caja = ? and {$fechaCampo} >= ? and {$fechaCampo} <= ?";
        $params = [$id, $fecha_inicio, $fecha_fin];
        if (!is_null($tipo_movimiento) && $tipo_movimiento !== 'TODOS') {
            $sql .= " and tblmovimientos_cajas.tipo_movimiento = ?";
            $params[] = $tipo_movimiento;
        }
        $sql .= " ORDER BY tblmovimientos_cajas.id asc;";

        $varhistcaj = DB::select($sql, $params);
        return collect($varhistcaj);
    }

    public function obtenerultimacuenta()
    {
        $sql = "SELECT id FROM tblcuentas ORDER BY id desc limit 1 ";
        $cuenta = DB::select($sql);
        return collect($cuenta);
    }

    public function obtenerultimacaja()
    {
        $sql = "SELECT id FROM tblcajas ORDER BY id desc limit 1 ";
        $caja = DB::select($sql);
        return collect($caja);
    }

    public function obtenerultimomovcuenta()
    {
        $sql = "SELECT id FROM tblmovimientos_cuentas ORDER BY id desc limit 1 ";
        $caja = DB::select($sql);
        return collect($caja);
    }

    public function obtenerultimomovcaja()
    {
        $sql = "SELECT id FROM tblmovimientos_cajas ORDER BY id desc limit 1 ";
        $caja = DB::select($sql);
        return collect($caja);
    }

    public function obteneracciones()
    {
        $varvista = Acciones::join('tblvistas', 'tblacciones.idvista', '=', 'tblvistas.id')
            ->join('tbldepartamentos', 'tblvistas.iddepartamento', '=', 'tbldepartamentos.id')
            ->select('tblacciones.id',
                'tblacciones.nombre_accion',
                'tblacciones.descripcion_accion',
                'tblacciones.created_at', 
                'tblacciones.created_by',
                'tblvistas.nombre as vista',
                'tbldepartamentos.nombre as nombre_departamento'
                )
            ->orderby('tblacciones.id', 'desc')
            ->get();
        return $varvista;
    }

    public function obtenerdepartamentos()
    {
        $vardepa = Departamentos::select('tbldepartamentos.id', 'tbldepartamentos.nombre')
            ->orderby('tbldepartamentos.id', 'desc')
            ->get();
        return $vardepa;
    }

    public function obtenerpuestos()
    {
        $varpuesto = Puestos::select('tblpuestos.id', 'tblpuestos.nombre')
            ->where('tblpuestos.estado', '=', 'A')
            ->get();
        return $varpuesto;
    }

    public function obtenerpuestosAll()
    {
        $varpuesto = Puestos::select('tblpuestos.id', 
        'tblpuestos.nombre', 
        'tblpuestos.estado',
        'tblpuestos.descripcion',
        'tblpuestos.created_at',
        'tblpuestos.updated_at',
        'tblpuestos.created_by',
        'tblpuestos.updated_by',
        )
            ->get();
        return $varpuesto;
    }

    public function obtenerestadosAll()
    {
        $var = DB::select("select id,
        nombre,
        created_at,
        updated_at,
        created_by,
        updated_by
        FROM tblestados");
        return collect($var);
    }


    public function obtenerciudadesAll()
    {
        $var = DB::select("select tblciudades.id,
        tblciudades.nombre,
        tblciudades.created_at,
        tblciudades.updated_at,
        tblciudades.created_by,
        tblciudades.updated_by,
        tblestados.id as id_estado,
        tblestados.nombre as estado
        FROM tblciudades
        inner join tblestados on tblciudades.idestado = tblestados.id");
        return collect($var);
    }

    public function obtenersucursalesAll()
    {
        $varsucursal = Sucursales::join('tblciudades', 'tblciudades.id', 'tblsucursales.idciudad')
            ->join('tblestados', 'tblestados.id', 'tblciudades.idestado')
            ->join('tblempresas', 'tblempresas.id', 'tblsucursales.idempresa')
            ->select(
                'tblsucursales.id',
                'tblsucursales.consecutivo as idconsecutivo',
                'tblsucursales.nombre',
                'tblsucursales.estado as status',
                'tblsucursales.telefono',
                'tblsucursales.idciudad',
                'tblciudades.nombre as ciudad',
                'tblestados.nombre as estado',
                'tblsucursales.colonia',
                'tblsucursales.calle',
                'tblsucursales.numero_interior',
                'tblsucursales.numero_exterior',
                'tblsucursales.codigo_postal',
                'tblempresas.nombre_empresa',
                'tblempresas.efectivo',
                'tblsucursales.idempresa',
                'tblsucursales.created_at',
                'tblsucursales.updated_at',
                'tblsucursales.created_by',
                'tblsucursales.updated_by'
            )
            ->orderby('tblsucursales.id', 'desc')
            ->orderBy('tblsucursales.estado', 'asc')
            ->get();
        return $varsucursal;
    }

    public function obtenersucursales()
    {
        $varsucursal = Sucursales::join('tblciudades', 'tblciudades.id', 'tblsucursales.idciudad')
            ->join('tblestados', 'tblestados.id', 'tblciudades.idestado')
            ->join('tblempresas', 'tblempresas.id', 'tblsucursales.idempresa')
            ->select(
                'tblsucursales.id',
                'tblsucursales.nombre',
                'tblsucursales.estado as status',
                'tblsucursales.telefono',
                'tblsucursales.idciudad',
                'tblciudades.nombre as ciudad',
                'tblestados.nombre as estado',
                'tblsucursales.colonia',
                'tblsucursales.calle',
                'tblsucursales.numero_interior',
                'tblsucursales.numero_exterior',
                'tblsucursales.codigo_postal',
                'tblempresas.nombre_empresa',
                'tblempresas.efectivo',
                'tblsucursales.created_at',
                'tblsucursales.updated_at',
                'tblsucursales.created_by',
                'tblsucursales.updated_by'
            )
            ->where('tblsucursales.estado', '=', 'A')
            ->orderby('tblsucursales.id', 'desc')
            ->get();
        return $varsucursal;
    }

    public function obtenersucursalesExcel()
    {
        $varsucursal = Sucursales::join('tblciudades', 'tblciudades.id', 'tblsucursales.idciudad')
            ->join('tblestados', 'tblestados.id', 'tblciudades.idestado')
            ->join('tblempresas', 'tblempresas.id', 'tblsucursales.idempresa')
            ->select(
                'tblsucursales.consecutivo as id',
                DB::raw('
        case 
        when tblempresas.efectivo = 1 then CONCAT(tblempresas.nombre_empresa,"(Efectivo)")
        else tblempresas.nombre_empresa
        end as nombre_empresa
        '),
                'tblsucursales.nombre',
                DB::raw('
        case 
        when tblsucursales.estado = "A" then "ACTIVA" 
        when tblsucursales.estado = "I" then "INACTIVA" 
        when tblsucursales.estado = "C" then "CERRADA" 
        end as status
        '),
                'tblsucursales.telefono',
                'tblestados.nombre as estado',
                'tblciudades.nombre as ciudad',
                'tblsucursales.colonia',
                'tblsucursales.calle',
                'tblsucursales.numero_interior',
                'tblsucursales.numero_exterior',
                'tblsucursales.codigo_postal',
                DB::raw(' CONCAT(tblsucursales.created_at," - ",tblsucursales.created_by) AS creado'),
                DB::raw('CONCAT(tblsucursales.updated_at," - ",tblsucursales.updated_by) AS actualizado')
            )
            ->orderby('tblsucursales.id', 'desc')
            ->get();
        return $varsucursal;
    }

    public function obtenersucursalxusuario(int $idusuario)
    {
        $varsucursalesxusuario = Sucursales::join('tblusuario_sucursales', 'tblsucursales.id', 'tblusuario_sucursales.idsucursal')
            ->join('users', 'tblusuario_sucursales.idusuario', 'users.id')
            ->select('tblsucursales.id', 'tblsucursales.nombre')
            ->where('tblusuario_sucursales.idusuario', '=', $idusuario)
            ->get();
        return $varsucursalesxusuario;
    }

    public function obtenerciudades()
    {
        $varciudad = Ciudades::join('tblestados', 'tblciudades.idestado', 'tblestados.id')
            ->select(
                'tblciudades.id',
                'tblestados.nombre as estado',
                'tblciudades.idestado',
                'tblciudades.nombre',
                'tblciudades.idestado'
            )
            ->get();
        return $varciudad;
    }

    public function obtenerempresas()
    {
        $condiciones = [
            ['tblempresas.estado', '=', 'A'],
            ['tblempresas.id', '>', 0]
        ];
        $varempresa = Empresas::select(
            'tblempresas.id',
            'tblempresas.nombre_empresa',
            'tblempresas.estado',
            'tblempresas.representada',
            'tblempresas.descripcion',
            'tblempresas.rfc',
            'tblempresas.efectivo',
            'tblempresas.icono',
            'tblempresas.marca_agua',
            'tblempresas.direccion_fiscal',
            'tblempresas.created_at',
            'tblempresas.updated_at',
            'tblempresas.created_by',
            'tblempresas.updated_by'
        )
            ->where($condiciones)
            ->orderby('tblempresas.id', 'desc')
            ->get();
        return $varempresa;
    }

    public function obtenerempresasconid0()
    {
        $varempresa = Empresas::select(
            'tblempresas.id',
            'tblempresas.nombre_empresa',
            'tblempresas.estado',
            'tblempresas.representada',
            'tblempresas.descripcion',
            'tblempresas.rfc',
            'tblempresas.efectivo',
            'tblempresas.icono',
            'tblempresas.marca_agua',
            'tblempresas.direccion_fiscal',
            'tblempresas.created_at',
            'tblempresas.updated_at',
            'tblempresas.created_by',
            'tblempresas.updated_by'
        )
            ->where('tblempresas.estado', '=', 'A')
            ->orderby('tblempresas.id', 'desc')
            ->get();
        return $varempresa;
    }

    public function obtenerempresasAll()
    {
        $varempresa = Empresas::select(
            'tblempresas.id',
            'tblempresas.nombre_empresa',
            'tblempresas.estado',
            'tblempresas.representada',
            'tblempresas.descripcion',
            'tblempresas.rfc',
            'tblempresas.registro_patronal_imss',
            'tblempresas.icono',
            'tblempresas.marca_agua',
            'tblempresas.efectivo',
            'tblempresas.direccion_fiscal',
            'tblempresas.created_at',
            'tblempresas.updated_at',
            'tblempresas.created_by',
            'tblempresas.updated_by'
        )
            ->where('tblempresas.id', '>', 0)
            ->orderby('tblempresas.id', 'desc')
            ->get();
        return $varempresa;
    }

    public function obtenerempresasTodas()
    {
        $varempresa = Empresas::select(
            'tblempresas.id',
            'tblempresas.nombre_empresa',
            'tblempresas.estado',
            'tblempresas.representada',
            'tblempresas.descripcion',
            'tblempresas.rfc',
            'tblempresas.icono',
            'tblempresas.marca_agua',
            'tblempresas.efectivo',
            'tblempresas.direccion_fiscal',
            'tblempresas.created_at',
            'tblempresas.updated_at',
            'tblempresas.created_by',
            'tblempresas.updated_by'
        )
            ->orderby('tblempresas.id', 'desc')
            ->get();
        return $varempresa;
    }

    public function obtenerempresasExcel()
    {
        $var = DB::select('select tblempresas.id,
            case 
                when tblempresas.estado = "A" then "ACTIVA" 
                when tblempresas.estado = "I" then "INACTIVA" 
                end as estado,
                tblempresas.nombre_empresa,
                tblempresas.descripcion,
                tblempresas.representada,
                tblempresas.rfc,
                tblempresas.registro_patronal_imss,
                tblempresas.direccion_fiscal,
                tblempresas.created_at,
                tblempresas.updated_at
            FROM tblempresas
            WHERE tblempresas.id > 0 order by tblempresas.id asc;');
        return collect($var);
    }

    public function obtenerempresaxid(int $id)
    {
        $varempresa = Empresas::select(
            'tblempresas.id',
            'tblempresas.nombre_empresa',
            'tblempresas.estado',
            'tblempresas.representada',
            'tblempresas.descripcion',
            'tblempresas.rfc',
            'tblempresas.icono',
            'tblempresas.marca_agua',
            'tblempresas.efectivo',
            'tblempresas.direccion_fiscal',
            'tblempresas.created_at',
            'tblempresas.updated_at',
            'tblempresas.created_by',
            'tblempresas.updated_by'
        )
            ->where('tblempresas.id', '=', $id)
            ->get();
        return $varempresa;
    }

    public function obtenerempleados()
    {
        $varempleado = empleados::select(
            'tblempleados.id',
            DB::raw('CONCAT_WS(" ",tblempleados.primer_nombre,tblempleados.segundo_nombre,tblempleados.apellido_paterno,tblempleados.apellido_materno) AS Nombre')
        )
            ->get();
        return $varempleado;
    }

    public function obtenerempleadosActivos()
    {
        $varempleado = empleados::select(
            'tblempleados.id',
            DB::raw('CONCAT(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) AS Nombre')
        )
            ->where('tblempleados.estado', '=', 'A')
            ->get();
        return $varempleado;
    }

    public function obtenerempleadoxid(int $id)
    {
        $varempleado = empleados::select(
            'tblempleados.id',
            DB::raw('CONCAT(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) AS Nombre')
        )
            ->where('tblempleados.id', '=', $id)
            ->get();
        return $varempleado;
    }

    public function obtenerbancos()
    {
        $varbanco = bancos::select('tblbancos.id', 'tblbancos.nombre')
            ->get();
        return $varbanco;
    }

    public function obtenerdias()
    {
        return Dias::select('id', 'dias')
            ->orderBy('id')
            ->get();
    }

    public function obtenerhorarios()
    {
        return Horarios::select('id', 'tipo', 'entrada', 'salida', 'descripcion')
            ->orderBy('id')
            ->get();
    }

    public function obtenerHorariosEmpleado(int $idEmpleado)
    {
        return DB::table('tblempleados_horarios')
            ->where('id_empleado', $idEmpleado)
            ->get()
            ->keyBy(fn ($row) => (int) $row->id_dia);
    }

    public function obtenerultimoempleado()
    {
        $varultimoempleado = empleados::latest('id')->first();
        return $varultimoempleado;
    }

    public function obtenernominas($fechaInicio = null, $fechaFin = null, $tipoNomina = null)
    {
        $query = Nominas_pagosenc::join(
            'tbltipo_nominas',
            'tblnominas_pagoenc.idtiponomina',
            '=',
            'tbltipo_nominas.id'
        )
            ->select(
                'tblnominas_pagoenc.id',
                DB::raw('DATE_FORMAT(tblnominas_pagoenc.fecha_inicio, "%d/%m/%Y") as fecha_inicio'),
                DB::raw('DATE_FORMAT(tblnominas_pagoenc.fecha_fin, "%d/%m/%Y") as fecha_fin'),
                'tblnominas_pagoenc.fecha_inicio as fecha_inicio_f',
                'tblnominas_pagoenc.fecha_fin as fecha_fin_f',
                'tblnominas_pagoenc.estado_nomina',
                'tblnominas_pagoenc.nombre_nomina',
                'tblnominas_pagoenc.comentarios',
                'tblnominas_pagoenc.idtiponomina',
                'tbltipo_nominas.tipo'
            );

        if ($fechaInicio && $fechaFin) {
            $query->where('tblnominas_pagoenc.fecha_inicio', '<=', $fechaFin)
                ->where('tblnominas_pagoenc.fecha_fin', '>=', $fechaInicio);
        }

        if ($tipoNomina) {
            $query->where('tblnominas_pagoenc.idtiponomina', $tipoNomina);
        }

        return $query->orderBy('tblnominas_pagoenc.fecha_inicio', 'desc')->get();
    }

    public function obtenerDatosGraficasNominas(array $ids)
    {
        $empty = [
            'labels' => [],
            'isr' => [],
            'imss' => [],
            'infonavit' => [],
            'bonos' => [],
            'horasExtras' => [],
            'incapacidades' => [],
            'faltas' => [],
            'vacaciones' => [],
            'diasNoLaborados' => [],
            'totales' => [
                'isr' => 0,
                'imss' => 0,
                'infonavit' => 0,
                'fonacot' => 0,
                'deudores' => 0,
            ],
        ];

        if (empty($ids)) {
            return $empty;
        }

        $rows = DB::table('tblnominas_pagodet')
            ->join('tblnominas_pagoenc', 'tblnominas_pagodet.idpagonomina', '=', 'tblnominas_pagoenc.id')
            ->whereIn('tblnominas_pagodet.idpagonomina', $ids)
            ->select(
                'tblnominas_pagoenc.nombre_nomina',
                DB::raw('SUM(tblnominas_pagodet.pago_isr) as isr'),
                DB::raw('SUM(tblnominas_pagodet.pago_imss) as imss'),
                DB::raw('SUM(tblnominas_pagodet.pago_infonavit) as infonavit'),
                DB::raw('SUM(tblnominas_pagodet.bono) as bonos'),
                DB::raw('SUM(tblnominas_pagodet.total_horas_extras) as horas_extras'),
                DB::raw('SUM(tblnominas_pagodet.dias_incapacidad) as incapacidades'),
                DB::raw('SUM(tblnominas_pagodet.faltas_reta_aus) as faltas'),
                DB::raw('SUM(tblnominas_pagodet.dias_vaciones) as vacaciones'),
                DB::raw('SUM(tblnominas_pagodet.dias_pendiente) as dias_no_laborados'),
                DB::raw('SUM(tblnominas_pagodet.fonacot) as fonacot'),
                DB::raw('SUM(tblnominas_pagodet.total_deudores) as deudores')
            )
            ->groupBy('tblnominas_pagoenc.id', 'tblnominas_pagoenc.nombre_nomina', 'tblnominas_pagoenc.fecha_inicio')
            ->orderBy('tblnominas_pagoenc.fecha_inicio', 'asc')
            ->get();

        $chartData = $empty;

        foreach ($rows as $row) {
            $chartData['labels'][] = $row->nombre_nomina;
            $chartData['isr'][] = round((float) $row->isr, 2);
            $chartData['imss'][] = round((float) $row->imss, 2);
            $chartData['infonavit'][] = round((float) $row->infonavit, 2);
            $chartData['bonos'][] = round((float) $row->bonos, 2);
            $chartData['horasExtras'][] = round((float) $row->horas_extras, 2);
            $chartData['incapacidades'][] = (int) $row->incapacidades;
            $chartData['faltas'][] = (int) $row->faltas;
            $chartData['vacaciones'][] = (int) $row->vacaciones;
            $chartData['diasNoLaborados'][] = (int) $row->dias_no_laborados;
            $chartData['totales']['isr'] += (float) $row->isr;
            $chartData['totales']['imss'] += (float) $row->imss;
            $chartData['totales']['infonavit'] += (float) $row->infonavit;
            $chartData['totales']['fonacot'] += (float) $row->fonacot;
            $chartData['totales']['deudores'] += (float) $row->deudores;
        }

        foreach ($chartData['totales'] as $key => $value) {
            $chartData['totales'][$key] = round($value, 2);
        }

        return $chartData;
    }

    public function obtenertipodescinfonavit()
    {
        $vartipodescuentoinfonavit = tipo_descuento_infonavit::select('tbltipoinfonavit.id', 'tbltipoinfonavit.Nombre')
            ->get();
        return $vartipodescuentoinfonavit;
    }


    public function obtenerbajas_empleados(int $id)
    {
        $varbajas = empleados_bajas::join(
            'tblempleados',
            'tblempleados.id',
            '=',
            'tblempleado_bajas.idempleado'
        )
            ->join("tblpuestos", "tblempleados.idpuesto", "=", "tblpuestos.id")
            ->join("tblnominas", "tblnominas.idempleado", "=", "tblempleados.id")
            ->join("tblempresas", "tblempresas.id", "=", "tblnominas.idempresa")
            ->join("tbltipoinfonavit", "tbltipoinfonavit.id", "=", "tblnominas.id_tipoinfonavit")
            ->select(
                'tblempleado_bajas.id',
                'tblempleado_bajas.idempleado',
                DB::raw('CONCAT(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) AS NombreEmpleado'),
                'tblempleado_bajas.tipo_baja',
                'tblempleado_bajas.descripcion',
                'tblempleado_bajas.fecha_baja',
                'tblempleado_bajas.dias_trabajados',
                'tblempleado_bajas.dias_gratificacion',
                'tblempleado_bajas.dias_aguinaldo',
                'tblempleado_bajas.dias_sueldo_a_deber',
                'tblempleado_bajas.dias_vacaciones',
                'tblempleado_bajas.cantidad_gratificacion',
                'tblempleado_bajas.cantidad_aguinaldo',
                'tblempleado_bajas.cantidad_sueldo',
                'tblempleado_bajas.cantidad_vacaciones',
                'tblempleado_bajas.cantidaddeduccion_infonavit',
                'tblempleado_bajas.cantidaddeduccion_transporte',
                'tblempleado_bajas.cantidaddeduccion_prestamo',
                'tblempleado_bajas.cantidaddeduccion_otros',
                'tblempleado_bajas.cantidadtotal_entregada',
                'tblempleado_bajas.cantidaddeduccion_imms',
                'tblempleado_bajas.dias_vacaciones_no_tomadas',
                'tblempleado_bajas.total_vacaciones_no_tomadas',
                'tblempleados.primer_nombre',
                'tblempleados.segundo_nombre',
                'tblempleados.apellido_paterno',
                'tblempleados.apellido_materno',
                'tblpuestos.nombre as puesto',
                'tblempleados.fecha_ingreso',
                'tblnominas.salario_fijo as salarioDiario',
                'tblnominas.excedente',
                'tblnominas.efectivo',
                'tblnominas.salario_bruto as salarioMensual',
                'tbltipoinfonavit.Nombre as infonavit',
                'tblempresas.nombre_empresa as empresa'
            )
            ->where('tblempleado_bajas.idempleado', '=', $id)
            ->orderby('tblempleado_bajas.id', 'desc')
            ->limit(1)
            ->get();
        return $varbajas;
    }

    public function obtenerlistaempleados()
    {
        $varlistaempleado = Empleados::join('tblnominas', 'tblempleados.id', '=', 'tblnominas.idempleado')
            ->join("tblpuestos", "tblempleados.idpuesto", "=", "tblpuestos.id")
            ->join("tblsucursales", "tblempleados.idsucursal", "=", "tblsucursales.id")
            ->join("tblciudades", "tblempleados.idciudad", "=", "tblciudades.id")
            ->join("tblbancos", "tblempleados.idbanco", "=", "tblbancos.id")
            ->join("tblempresas", "tblempresas.id", "=", "tblnominas.idempresa")
            ->join("tbltipoinfonavit", "tbltipoinfonavit.id", "=", "tblnominas.id_tipoinfonavit")
            ->leftjoin("users", "tblempleados.id", "=", "users.idempleado")
            ->select(
                'tbltipoinfonavit.Nombre as nombreinfonavit',
                'tblnominas.id as idnomina',
                'tblempleados.id as idempleado',
                'tblpuestos.id as idpuesto',
                'tblempleados.primer_nombre',
                'tblempleados.segundo_nombre',
                'tblempleados.apellido_paterno',
                'tblempleados.apellido_materno',
                'tblempleados.telefono',
                'tblempleados.correo',
                'tblpuestos.nombre as puesto',
                'tblsucursales.nombre as sucursal',
                'tblciudades.nombre as ciudad',
                'tblempleados.calle',
                'tblempleados.colonia',
                'tblempleados.numero_interior',
                'tblempleados.numero_exterior',
                'tblempleados.codigo_postal',
                'tblempleados.sexo',
                'tblempleados.fecha_nacimiento',
                'tblempleados.foto',
                'tblempleados.nombre_foto',
                'tblempleados.ruta_contrato',
                'tblempleados.status_contrato',
                'tblempleados.archivo_baja',
                'tblnominas.salario_bruto',
                'tblnominas.salario_fijo',
                'tblbancos.nombre as banco',
                'tblnominas.numero_tarjeta',
                'tblnominas.numero_cuenta',
                'tblempleados.rfc',
                'tblempleados.nss',
                'tblempleados.tipo_sangre',
                'tblempleados.contacto_emergencia',
                'tblempleados.telefono_emergencia',
                'tblempleados.estado',
                'tblempleados.estado_civil',
                'tblempleados.descripcion_estado',
                'tblempleados.fecha_ingreso',
                'tblempleados.nacionalidad',
                'tblempleados.grado_estudio',
                'tblempleados.curp',
                'tblempleados.tipo_contratacion',
                DB::raw('DATE_FORMAT(tblempleados.fecha_determinado, "%d/%m/%Y") as fecha_determinado'),
                'tblnominas.salario_fijo',
                'tblnominas.excedente',
                'tblnominas.efectivo',
                'tblnominas.factor_sua',
                'tblnominas.descuento_quincenal',
                'tblnominas.numero_credito_infonavit',
                'tblnominas.fecha_ingreso_imss',
                'tblempresas.id',
                'tblempresas.nombre_empresa',
                'tblempresas.efectivo as empresaEfectivo',
                'users.id as id_user'
            )
            ->orderby('tblempleados.id', 'asc')
            ->get();

        return $varlistaempleado;

    }

    public function obtenerEmpleadosFiltro(string $status, $idSucursal = null, $idPuesto = null)
    {
        $query = Empleados::join('tblnominas', 'tblempleados.id', '=', 'tblnominas.idempleado')
            ->join("tblpuestos", "tblempleados.idpuesto", "=", "tblpuestos.id")
            ->join("tblsucursales", "tblempleados.idsucursal", "=", "tblsucursales.id")
            ->join("tblciudades", "tblempleados.idciudad", "=", "tblciudades.id")
            ->join("tblbancos", "tblempleados.idbanco", "=", "tblbancos.id")
            ->join("tblempresas", "tblempresas.id", "=", "tblnominas.idempresa")
            ->join("tbltipoinfonavit", "tbltipoinfonavit.id", "=", "tblnominas.id_tipoinfonavit")
            ->select(
                'tbltipoinfonavit.id as id_tipoinfonavit',
                'tbltipoinfonavit.Nombre as nombreinfonavit',
                'tblnominas.id as idnomina',
                'tblempleados.id as idempleado',
                'tblpuestos.id as idpuesto',
                'tblempleados.idsucursal',
                'tblempleados.primer_nombre',
                'tblempleados.segundo_nombre',
                'tblempleados.apellido_paterno',
                'tblempleados.apellido_materno',
                'tblempleados.telefono',
                'tblempleados.correo',
                'tblpuestos.nombre as puesto',
                'tblsucursales.nombre as sucursal',
                'tblciudades.nombre as ciudad',
                'tblempleados.calle',
                'tblempleados.colonia',
                'tblempleados.numero_interior',
                'tblempleados.numero_exterior',
                'tblempleados.codigo_postal',
                'tblempleados.sexo',
                DB::raw('DATE_FORMAT(tblempleados.fecha_nacimiento, "%d/%m/%Y") as fecha_nacimiento'),
                'tblempleados.foto',
                'tblempleados.nombre_foto',
                'tblempleados.ruta_contrato',
                'tblempleados.status_contrato',
                'tblempleados.archivo_baja',
                'tblnominas.salario_bruto',
                'tblnominas.salario_fijo',
                'tblnominas.zona',
                'tblbancos.nombre as banco',
                'tblnominas.numero_tarjeta',
                'tblnominas.numero_cuenta',
                'tblempleados.rfc',
                'tblempleados.nss',
                'tblempleados.tipo_sangre',
                'tblempleados.contacto_emergencia',
                'tblempleados.telefono_emergencia',
                'tblempleados.estado',
                'tblempleados.estado_civil',
                'tblempleados.descripcion_estado',
                DB::raw('DATE_FORMAT(tblempleados.fecha_ingreso, "%d/%m/%Y") as fecha_ingreso'),
                'tblempleados.nacionalidad',
                'tblempleados.grado_estudio',
                'tblempleados.curp',
                'tblempleados.tipo_contratacion',
                DB::raw('DATE_FORMAT(tblempleados.fecha_determinado, "%d/%m/%Y") as fecha_determinado'),
                'tblnominas.salario_fijo',
                'tblnominas.excedente',
                'tblnominas.efectivo',
                'tblnominas.factor_sua',
                'tblnominas.descuento_quincenal',
                'tblnominas.numero_credito_infonavit',
                DB::raw('DATE_FORMAT(tblnominas.fecha_ingreso_imss, "%d/%m/%Y") as fecha_ingreso_imss'),
                'tblempresas.id as idempresa',
                'tblempresas.nombre_empresa',
                'tblempresas.registro_patronal_imss',
                'tblempresas.efectivo as empresaEfectivo'
            );

        if ($status !== '') {
            $query->where('tblempleados.estado', '=', $status);
        }

        if (!empty($idSucursal)) {
            $query->where('tblempleados.idsucursal', '=', $idSucursal);
        }

        if (!empty($idPuesto)) {
            $query->where('tblempleados.idpuesto', '=', $idPuesto);
        }

        return $query->orderby('tblempleados.id', 'desc')->get();
    }

    public function obtenerlistaempleadosSinUser()
    {
        $varlistaempleado = Empleados::join(
            'tblnominas',
            'tblempleados.id',
            '=',
            'tblnominas.idempleado'
        )
            ->join("tblpuestos", "tblempleados.idpuesto", "=", "tblpuestos.id")
            ->join("tblsucursales", "tblempleados.idsucursal", "=", "tblsucursales.id")
            ->join("tblciudades", "tblempleados.idciudad", "=", "tblciudades.id")
            ->join("tblbancos", "tblempleados.idbanco", "=", "tblbancos.id")
            ->join("tblempresas", "tblempresas.id", "=", "tblnominas.idempresa")
            ->join("tbltipoinfonavit", "tbltipoinfonavit.id", "=", "tblnominas.id_tipoinfonavit")
            ->select(
                'tbltipoinfonavit.id as idinfonavit',
                'tbltipoinfonavit.Nombre as nombreinfonavit',
                'tblnominas.id as idnomina',
                'tblempleados.id as idempleado',
                'tblpuestos.id as idpuesto',
                'tblempleados.primer_nombre',
                'tblempleados.segundo_nombre',
                'tblempleados.apellido_paterno',
                'tblempleados.apellido_materno',
                'tblempleados.telefono',
                'tblempleados.correo',
                'tblpuestos.nombre as puesto',
                'tblsucursales.nombre as sucursal',
                'tblciudades.nombre as ciudad',
                'tblempleados.calle',
                'tblempleados.colonia',
                'tblempleados.numero_interior',
                'tblempleados.numero_exterior',
                'tblempleados.codigo_postal',
                'tblempleados.sexo',
                'tblempleados.fecha_nacimiento',
                'tblempleados.foto',
                'tblempleados.archivo_baja',
                'tblnominas.salario_bruto',
                'tblnominas.salario_fijo',
                'tblbancos.nombre as banco',
                'tblnominas.numero_tarjeta',
                'tblnominas.numero_cuenta',
                'tblempleados.rfc',
                'tblempleados.nss',
                'tblempleados.tipo_sangre',
                'tblempleados.contacto_emergencia',
                'tblempleados.telefono_emergencia',
                'tblempleados.estado',
                'tblempleados.estado_civil',
                'tblempleados.descripcion_estado',
                'tblempleados.fecha_ingreso',
                'tblempleados.nacionalidad',
                'tblempleados.grado_estudio',
                'tblempleados.curp',
                'tblempleados.tipo_contratacion',
                DB::raw('DATE_FORMAT(tblempleados.fecha_determinado, "%d/%m/%Y") as fecha_determinado'),
                'tblnominas.salario_fijo',
                'tblnominas.excedente',
                'tblnominas.efectivo',
                'tblnominas.factor_sua',
                'tblnominas.descuento_quincenal',
                'tblnominas.numero_credito_infonavit',
                'tblnominas.fecha_ingreso_imss',
                'tblempresas.id',
                'tblempresas.nombre_empresa'
            )
            ->where("tblempleados.foto", "<>", "1")
            ->orderby("tblempleados.primer_nombre", "ASC")
            ->get();

        return $varlistaempleado;
    }

    public function obtenerPerfiles()
    {
        $varempl = DB::select('SELECT tblperfiles.id, tblperfiles.nombre FROM  tblperfiles;');
        return collect($varempl);
    }

    public function obtenerAccionesdePerfiles()
    {
        $varempl = DB::select('SELECT  
                tblperfiles.id, 
                tblperfiles.nombre, 
                tblperfil_acciones.id as idperfilAcc,
                tblacciones.id as idaccion, 
                tblacciones.descripcion_accion, 
                tblvistas.id as idVista ,
                tblvistas.nombre as nombreVista, 
                tblvistas.iddepartamento, 
                tbldepartamentos.nombre as nombreDepartamento 
            FROM tblacciones 
            INNER JOIN tblvistas on tblvistas.id = tblacciones.idvista 
            INNER JOIN tbldepartamentos on tblvistas.iddepartamento = tbldepartamentos.id 
            INNER JOIN tblperfil_acciones on tblacciones.id = tblperfil_acciones.idaccion 
            INNER JOIN tblperfiles on tblperfil_acciones.idperfil = tblperfiles.id 
            ORDER BY tblperfiles.nombre desc;');
        return collect($varempl);
    }

    public function obtenerPerfilesxAccion($idperfil)
    {
        $varempl = DB::select('SELECT tblperfiles.nombre, tblacciones.id as idaccion, 
                tblacciones.nombre_accion, tblvistas.id as idVista ,
                tblvistas.nombre as nombreVista, tblvistas.iddepartamento, 
                tbldepartamentos.nombre as nombreDepartamento 
            FROM tblacciones 
            INNER JOIN tblvistas on tblvistas.id = tblacciones.idvista 
            INNER JOIN tbldepartamentos on tblvistas.iddepartamento = tbldepartamentos.id 
            INNER JOIN tblperfil_acciones on tblacciones.id = tblperfil_acciones.idaccion 
            INNER JOIN tblperfiles on tblperfil_acciones.idperfil = tblperfiles.id 
            WHERE tblperfil_acciones.idperfil = ?;', [$idperfil]);
        return collect($varempl);
    }

    public function obtenerpantallasxUser($idUser, $idVista)
    {
        $varempl = DB::select('SELECT tblusuario_pantallas.idvista, tblusuario_pantallas.idusuario FROM tblusuario_pantallas 
            WHERE tblusuario_pantallas.idusuario = ? AND tblusuario_pantallas.idvista = ?;', [$idUser, $idVista]);
        return collect($varempl);
    }

    public function obtenerSucursalesUser($idUser, $idSuc)
    {
        $varempl = DB::select('SELECT * FROM  tblusuario_sucursales WHERE idusuario = ? and idsucursal = ?;', [$idUser, $idSuc]);
        return collect($varempl);
    }

    public function obtenerSucursalesxUser($idUser)
    {
        $varempl = DB::select('SELECT * FROM tblusuario_sucursales WHERE idusuario = ?;', [$idUser]);
        return collect($varempl);
    }

    public function obtenerSucursalesXUsuario(int $idUsuario)
    {
        $varempl = Sucursales::join('tblusuario_sucursales', 'tblsucursales.id', '=', 'tblusuario_sucursales.idsucursal')
            ->select('tblusuario_sucursales.idusuario', 'tblusuario_sucursales.idsucursal', 'tblsucursales.nombre')
            ->where('tblusuario_sucursales.idusuario', $idUsuario)
            ->groupBy('tblsucursales.id')
            ->get();
        return collect($varempl);
    }

    public function obteneraccionesxUser($idUser, $idAccion)
    {
        $varempl = DB::select('SELECT tblusuario_acciones.idacciones, tblusuario_acciones.idusuario 
            FROM tblusuario_acciones 
            WHERE tblusuario_acciones.idusuario = ? and tblusuario_acciones.idacciones = ?;', [$idUser, $idAccion]);
        return collect($varempl);
    }

    public function obteneraccionesxPerfiles($idaccion, $idperfil)
    {
        $varempl = DB::select('SELECT * FROM tblperfil_acciones 
            WHERE tblperfil_acciones.idaccion = ? and tblperfil_acciones.idperfil = ?;', [$idaccion, $idperfil]);
        return collect($varempl);
    }

    public function extraerempleados()
    {
        $varlistaempleado = Empleados::join(
            'tblnominas',
            'tblempleados.id',
            '=',
            'tblnominas.idempleado'
        )
            ->join("tblpuestos", "tblempleados.idpuesto", "=", "tblpuestos.id")
            ->join("tblsucursales", "tblempleados.idsucursal", "=", "tblsucursales.id")
            ->join("tblciudades", "tblempleados.idciudad", "=", "tblciudades.id")
            ->join("tblbancos", "tblempleados.idbanco", "=", "tblbancos.id")
            ->join("tblempresas", "tblempresas.id", "=", "tblnominas.idempresa")
            ->join("tbltipoinfonavit", "tbltipoinfonavit.id", "=", "tblnominas.id_tipoinfonavit")
            ->select(
                'tblempleados.id as idempleado',
                DB::raw('CASE
            WHEN tblempresas.efectivo = 1 then CONCAT(tblempresas.nombre_empresa," (Efectivo)") 
            else tblempresas.nombre_empresa
            end AS nombreEmpresa'),
                'tblsucursales.nombre as sucursal',
                'tblempleados.apellido_paterno',
                'tblempleados.apellido_materno',
                'tblempleados.primer_nombre',
                'tblempleados.segundo_nombre',
                'tblempleados.telefono',
                'tblempleados.correo',
                'tblempleados.nacionalidad',
                'tblempleados.fecha_nacimiento',
                'tblempleados.grado_estudio',
                'tblpuestos.nombre as puesto',
                'tblempleados.rfc',
                'tblempleados.curp',
                'tblempleados.nss',
                'tblempleados.sexo',
                'tblempleados.tipo_sangre',
                'tblempleados.estado_civil',
                'tblempleados.calle',
                'tblempleados.colonia',
                'tblempleados.numero_interior',
                'tblempleados.numero_exterior',
                'tblempleados.codigo_postal',
                'tblciudades.nombre as ciudad',
                'tblempleados.fecha_ingreso',
                'tblempleados.fecha_baja',
                'tblempleados.estado',
                'tblempleados.descripcion_estado',
                'tblnominas.id as idnomina',
                'tblnominas.fecha_ingreso_imss',
                'tblnominas.salario_bruto',
                'tblnominas.salario_fijo',
                'tblnominas.salario_fijo',
                'tblnominas.excedente',
                'tblnominas.efectivo',
                'tbltipoinfonavit.Nombre as nombreinfonavit',
                'tblnominas.factor_sua',
                'tblnominas.descuento_quincenal',
                'tblnominas.numero_credito_infonavit',
                'tblbancos.nombre as banco',
                DB::raw('CONCAT("´",tblnominas.numero_tarjeta) AS numero_tarjeta'),
                DB::raw('CONCAT("´",tblnominas.numero_cuenta) AS numero_cuenta'),
                'tblempleados.contacto_emergencia',
                'tblempleados.telefono_emergencia'
            )
            ->orderby('tblempleados.id', 'asc')
            ->get();
        return $varlistaempleado;

    }


    public function obtenerlistaempleadoid(int $id)
    {
        $varlistaempleado = Empleados::join(
            'tblnominas',
            'tblempleados.id',
            '=',
            'tblnominas.idempleado'
        )
            ->join("tblpuestos", "tblempleados.idpuesto", "=", "tblpuestos.id")
            ->join("tblsucursales", "tblempleados.idsucursal", "=", "tblsucursales.id")
            ->join("tblciudades", "tblempleados.idciudad", "=", "tblciudades.id")
            ->join("tblestados", "tblestados.id", "=", "tblciudades.idestado")
            ->join("tblbancos", "tblempleados.idbanco", "=", "tblbancos.id")
            ->join("tblempresas", "tblempresas.id", "=", "tblnominas.idempresa")
            ->join("tbltipoinfonavit", "tbltipoinfonavit.id", "=", "tblnominas.id_tipoinfonavit")
            ->select(
                'tbltipoinfonavit.id as idinfonavit',
                'tbltipoinfonavit.Nombre as nombreinfonavit',
                'tblnominas.id as idnom',
                'tblempleados.id as idempleado',
                'tblpuestos.id as idpuesto',
                'tblempleados.idsucursal',
                'tblbancos.id as idbanco',
                'tblempleados.primer_nombre',
                'tblempleados.segundo_nombre',
                'tblempleados.apellido_paterno',
                'tblempleados.apellido_materno',
                DB::raw('CONCAT(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) AS Nombre'),
                'tblempleados.telefono',
                'tblempleados.correo',
                'tblpuestos.nombre as puesto',
                'tblsucursales.nombre as sucursal',
                DB::raw('
            case 
            when tblempleados.numero_interior != "0" then CONCAT(tblempleados.colonia," ",tblempleados.calle," No. Int ",tblempleados.numero_interior," No. Ext ",tblempleados.numero_exterior,", ",tblciudades.nombre,", ",tblestados.nombre,", C.P. ",tblempleados.codigo_postal)
            else
            CONCAT(tblempleados.colonia," ",tblempleados.calle," No. Ext ",tblempleados.numero_exterior,", ",tblciudades.nombre,", ",tblestados.nombre,", C.P. ",tblempleados.codigo_postal)
            end  AS direccionEmp
            '),
                'tblciudades.nombre as ciudad',
                'tblempleados.calle',
                'tblempleados.colonia',
                'tblempleados.numero_interior',
                'tblempleados.numero_exterior',
                'tblempleados.codigo_postal',
                'tblempleados.sexo',
                'tblempleados.fecha_nacimiento',
                'tblempleados.foto',
                'tblempleados.nombre_foto',
                'tblempleados.ruta_contrato',
                'tblempleados.status_contrato',
                'tblnominas.salario_bruto',
                'tblnominas.salario_fijo',
                'tblnominas.zona',
                'tblbancos.nombre as banco',
                'tblnominas.numero_tarjeta',
                'tblnominas.numero_cuenta',
                'tblempleados.rfc',
                'tblempleados.nss',
                'tblempleados.nacionalidad',
                'tblempleados.grado_estudio',
                'tblempleados.curp',
                'tblempleados.tipo_sangre',
                'tblempleados.contacto_emergencia',
                'tblempleados.telefono_emergencia',
                'tblempleados.estado',
                'tblempleados.estado_civil',
                'tblempleados.descripcion_estado',
                'tblempleados.fecha_ingreso',
                'tblnominas.salario_fijo',
                'tblnominas.excedente',
                'tblnominas.efectivo',
                'tblnominas.idbanca',
                'tblnominas.factor_sua',
                'tblnominas.descuento_quincenal',
                'tblnominas.numero_credito_infonavit',
                'tblempresas.id',
                'tblnominas.fecha_ingreso_imss',
                'tblempresas.descripcion as nombre_empresa',
                'tblempresas.direccion_fiscal',
                'tblempresas.representada',
                'tblempresas.rfc as rfcEmpresa',
                'tblempleados.colonia_f',
                'tblempleados.calle_f',
                'tblempleados.no_interior_f',
                'tblempleados.no_exterior_f',
                'tblempleados.codigo_postal_f',
                'tblempleados.tipo_contratacion',
                'tblempleados.fecha_determinado'
            )
            ->where('tblempleados.id', '=', $id)
            ->get();
        return $varlistaempleado;
    }

    public function obtenerContratosPorVencer(int $diasAnticipacion = 7)
    {
        $hasta = Carbon::now()->addDays($diasAnticipacion)->format('Y-m-d');

        return Empleados::join('tblpuestos', 'tblempleados.idpuesto', '=', 'tblpuestos.id')
            ->join('tblsucursales', 'tblempleados.idsucursal', '=', 'tblsucursales.id')
            ->select(
                'tblempleados.id as idempleado',
                'tblempleados.primer_nombre',
                'tblempleados.segundo_nombre',
                'tblempleados.apellido_paterno',
                'tblempleados.apellido_materno',
                DB::raw('CONCAT_WS(" ", tblempleados.primer_nombre, tblempleados.segundo_nombre, tblempleados.apellido_paterno, tblempleados.apellido_materno) AS nombre'),
                'tblpuestos.nombre as puesto',
                'tblsucursales.nombre as sucursal',
                'tblempleados.fecha_determinado',
                DB::raw('DATE_FORMAT(tblempleados.fecha_determinado, "%d/%m/%Y") as fecha_vencimiento_fmt'),
                DB::raw('DATEDIFF(tblempleados.fecha_determinado, CURDATE()) as dias_restantes')
            )
            ->where('tblempleados.estado', 'A')
            ->where('tblempleados.tipo_contratacion', 'DEFINIDA')
            ->whereNotNull('tblempleados.fecha_determinado')
            ->where('tblempleados.fecha_determinado', '<=', $hasta)
            ->orderBy('tblempleados.fecha_determinado')
            ->get();
    }

    public function obtenernominasporid($id)
    {
       $var = DB::select('select
                tblnominas_pagoenc.id as pagoenc_id,
                tblnominas_pagoenc.idtiponomina,
                tblnominas_pagoenc.estado_nomina,
                tblnominas_pagoenc.fecha_fin,
                tblnominas_pagoenc.fecha_inicio,
                tblnominas_pagoenc.nombre_nomina,
                tblnominas_pagoenc.created_at as created,
                tblempleados.id as idempleado,
                tblnominas_pagodet.id,
                tblnominas_pagodet.idpagonomina,
                tblempleados.primer_nombre,
                tblempleados.segundo_nombre,
                tblempleados.apellido_paterno,
                tblempleados.apellido_materno,
                tblpuestos.nombre as puesto,
                tblsucursales.id as idsucursal,
                tblsucursales.nombre as sucursal,
                tblnominas_pagodet.horas_extras,
                tblnominas_pagodet.horas_extras_pago_f,
                tblnominas_pagodet.horas_extras_pago_e,
                tblnominas_pagodet.total_horas_extras,
                tblnominas_pagodet.dias_incapacidad,
                tblnominas_pagodet.faltas_reta_aus,
                tblnominas_pagodet.total_faltas_reta_aus,
                tblnominas_pagodet.dias_laborados,
                tblnominas.excedente,
                tblnominas.efectivo,
                tblnominas.salario_fijo,
                tblnominas_pagodet.salario_diario_integrado,
                tblnominas_pagodet.total_sueldo,
                tblnominas_pagodet.sueldo_fiscal,
                tblnominas_pagodet.sueldo_excedente,
                tblnominas_pagodet.sueldo_efectivo,
                tblnominas_pagodet.deudores_fiscal,
                tblnominas_pagodet.deudores_no_fiscal,
                tblnominas_pagodet.total_deudores,
                tblnominas_pagodet.pago_infonavit,
                tblnominas_pagodet.pago_imss,
                tblnominas_pagodet.pago_isr,
                tblnominas_pagodet.pago_subsidio,
                tblnominas_pagodet.ahorro,
                tblnominas_pagodet.dias_prima_vacacional,
                tblnominas_pagodet.pago_prima_vacacional_exce,
                tblnominas_pagodet.pago_prima_vacacional_fis,
                tblnominas_pagodet.pago_prima_vacacional,
                tblnominas_pagodet.dias_vaciones,
                tblnominas_pagodet.pago_dias_vacaciones_fis,
                tblnominas_pagodet.pago_dias_vacaciones_exce,
                tblnominas_pagodet.pago_dias_vacaciones,
                tblnominas_pagodet.dias_pendiente,
                tblnominas_pagodet.bono,
                tblnominas_pagodet.viaticos,
                tblnominas_pagodet.transporte,
                tblnominas_pagodet.percepcion_extraordinaria,
                tblnominas_pagodet.despensa,
                tblnominas_pagodet.fonacot,
                tblnominas_pagodet.otros,
                tblnominas_pagodet.dias_descanso,
                tblnominas_pagodet.pago_dias_descanso,
                tblnominas_pagodet.dias_prima_dominical,
                tblnominas_pagodet.pago_prima_dominical,
                tblnominas_pagodet.total_nomina_fiscal,
                tblnominas_pagodet.total_apagar_excedente,
                tblnominas_pagodet.total_efectivo,
                tblnominas_pagodet.pago_nomina_fiscal_global,
                tblnominas_pagodet.pago_nomina_excedente_global,
                tblnominas_pagodet.pago_efectivo_cajas,
                tblnominas_pagodet.total_apagar,
                tblnominas_pagodet.entrega,
                tblbancos.nombre as banco,
                tblnominas.idbanca,
                tblnominas.numero_tarjeta,
                tblnominas.numero_cuenta
                from tblnominas_pagodet 
                inner join tblempleados on tblempleados.id = tblnominas_pagodet.idempleado
                inner join tblpuestos on tblempleados.idpuesto = tblpuestos.id
                inner join tblsucursales on  tblempleados.idsucursal = tblsucursales.id
                inner join tblnominas on tblempleados.id = tblnominas.idempleado
                inner join tblbancos on tblempleados.idbanco = tblbancos.id
                inner join tblnominas_pagoenc on tblnominas_pagodet.idpagonomina = tblnominas_pagoenc.id
                where tblnominas_pagodet.idpagonomina = ? 
                order by tblempleados.id asc;',[$id]);
        return collect($var);
        // and tblnominas_pagodet.id > 381
    }

    public function obtenerempleadoxnomina($id)
    {
        $var = DB::select('SELECT * FROM tblnominas_pagodet WHERE id = ?;', [$id]);
        return collect($var);
    }

    public function obtenernominasporidexport($id)
    {
        $varnomina = DB::select('select tblsucursales.nombre as nom_suc,
        tblpuestos.nombre as nom_pues,
        tblempleados.id as id_emp,
        CONCAT(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) as nom_emp,
 		tblnominas.salario_fijo as salario_diario,
        tblnominas_pagodet.salario_diario_integrado,
        tblnominas.excedente as salario_excedente,
        tblnominas_pagodet.dias_laborados,
        tblnominas_pagodet.sueldo_fiscal,
        tblnominas_pagodet.sueldo_excedente,
        tblnominas_pagodet.total_sueldo,
        
        tblnominas_pagodet.dias_incapacidad,
        tblnominas_pagodet.faltas_reta_aus,
        tblnominas_pagodet.total_faltas_reta_aus,
        
        tblnominas_pagodet.horas_extras,
        tblnominas_pagodet.horas_extras_pago_f,
        tblnominas_pagodet.horas_extras_pago_e,
        tblnominas_pagodet.total_horas_extras,

        tblnominas_pagodet.dias_descanso,
        tblnominas_pagodet.pago_dias_descanso,
        tblnominas_pagodet.dias_prima_dominical,
        tblnominas_pagodet.pago_prima_dominical,
        
        
        tblnominas_pagodet.pago_infonavit,
        tblnominas_pagodet.pago_imss,
        tblnominas_pagodet.pago_subsidio,
        tblnominas_pagodet.pago_isr,
        tblnominas_pagodet.fonacot,

        tblnominas_pagodet.deudores_fiscal,

        tblnominas_pagodet.bono,
        tblnominas_pagodet.viaticos,
        tblnominas_pagodet.otros,
        tblnominas_pagodet.despensa,
        tblnominas_pagodet.percepcion_extraordinaria,

        tblnominas_pagodet.dias_vaciones,
        tblnominas_pagodet.pago_dias_vacaciones_fis,
        tblnominas_pagodet.pago_dias_vacaciones_exce,
        tblnominas_pagodet.pago_dias_vacaciones,

        tblnominas_pagodet.dias_prima_vacacional ,
        tblnominas_pagodet.pago_prima_vacacional_fis,
        tblnominas_pagodet.pago_prima_vacacional_exce,
        tblnominas_pagodet.pago_prima_vacacional,

        tblnominas_pagodet.total_nomina_fiscal,
        tblnominas_pagodet.total_apagar_excedente,
        tblnominas_pagodet.total_apagar,
        tblbancos.nombre as banco,
        tblnominas.numero_cuenta
        from tblnominas_pagoenc 
        inner join tblnominas_pagodet on tblnominas_pagoenc.id = tblnominas_pagodet.idpagonomina
        inner join tblempleados on  tblempleados.id = tblnominas_pagodet.idempleado
        inner join tblnominas on tblnominas.idempleado = tblempleados.id 
        inner join tblsucursales on  tblsucursales.id = tblempleados.idsucursal
        inner join tblpuestos on  tblpuestos.id = tblempleados.idpuesto
		inner join tblbancos on  tblbancos.id = tblnominas.idbancos 
        WHERE  tblnominas_pagoenc.id = ? order by tblempleados.id ASC;',[$id]);

        return collect($varnomina);
    }

    public function obtenerformatnominasporidexport($id)
    {
        $varempl = DB::select('
        select 
        emp.id as id_empleado,
        case 
        when emp.segundo_nombre = " " then CONCAT (emp.primer_nombre, " ",emp.apellido_paterno," ",emp.apellido_materno ) 
        else
        CONCAT (emp.primer_nombre," ",emp.segundo_nombre," ",emp.apellido_paterno," ",emp.apellido_materno ) 
        end as Nombre_Empleado,
        0.00 as "1",
        0.00 as "2",
        0.00 as "3",
        0.00 as "4",
        0.00 as "5",
        0.00 as "6",
        0.00 as "7",
        0.00 as "8",
        0.00 as "9"
        from tblnominas_pagodet nomdet 
        inner join tblempleados emp on nomdet.idempleado = emp.id
        inner join  tblpuestos puest on emp.idpuesto = puest.id
        inner join tblsucursales suc on emp.idsucursal = suc.id
        inner join tblnominas nom on emp.id = nom.idempleado
        inner join tblbancos banco on emp.idbanco = banco.id 
        inner join tblnominas_pagoenc nomenc on nomdet.idpagonomina = nomenc.id
        inner join tbltipo_nominas tiponom on nomenc.idtiponomina = tiponom.id
        where idpagonomina = ? ORDER BY id_empleado ASC;', [$id]);
        return collect($varempl);
    }

    public function obteneempleadonomaeditar(int $idpagonom, int $idempleado)
    {
        $varobtenernomemp = Nominas_pagosenc::join(
            'tblnominas_pagodet',
            'tblnominas_pagoenc.id',
            '=',
            'tblnominas_pagodet.idpagonomina'
        )
            ->select('tblnominas_pagoenc.id', 'tblnominas_pagoenc.idtiponomina', 'tblnominas_pagodet.id as idpadodet', 'tblnominas_pagodet.idempleado', 'tblnominas_pagodet.dias_laborados', 'tblnominas_pagodet.deudores_fiscal', 'tblnominas_pagodet.deudores_no_fiscal', 'pago_infonavit', 'pago_imss', 'pago_subsidio', 'pago_isr', 'bono', 'viaticos', 'transporte')
            ->where(['tblnominas_pagoenc.id' => $idpagonom, 'tblnominas_pagodet.idempleado' => $idempleado])
            ->get();
        return $varobtenernomemp;
    }

    public function obtenerplazos()
    {
        $varplazos = plazos::select('tblplazos_vales.id', 'tblplazos_vales.plazos', 'tblplazos_vales.tipo')
            ->get();
        return $varplazos;
    }

    public function obtenerdatosusuariologueado(int $idusuario)
    {
        $datosusuario = user::join('tblempleados', 'users.idempleado', 'tblempleados.id')
            ->select('tblempleados.primer_nombre', 'tblempleados.segundo_nombre', 'tblempleados.apellido_paterno', 'apellido_materno')
            ->where('users.id', '=', $idusuario)
            ->get();
        return $datosusuario;
    }
    
    public function obtenertasasnomina()
    {
        $varlistatasa = tasas::select('id', 'nombre', 'tipo', 'porcentaje')
            ->get();
        return $varlistatasa;
    }

    public function obtenertasaxid(int $id)
    {
        $varlistatasa = tasas::select('id', 'nombre', 'tipo', 'porcentaje')
            ->where('id', '=', $id)
            ->get();
        return $varlistatasa;
    }

    public function obtenerultimpresnom()
    {
        $ultimocredito = DB::select('select id from tblcreditos_empleado order by id desc limit 1');
        return collect($ultimocredito);
    }

    public function obtenerultimpresnomtemp()
    {
        $ultimocredito = DB::select('select id from temptblcreditos_empleado order by id desc limit 1');
        return collect($ultimocredito);
    }

    public function validasitieneprestamo(int $idempleado, string $tipocredito)
    {
        $ultimocredito = DB::select('select id_empleado,tipo_credito,estado from tblcreditos_empleado where id_empleado = ? and  estado= "A" and tipo_credito = ?;', [$idempleado, $tipocredito]);
        return collect($ultimocredito);
    }

    public function validasaldado(int $idpres)
    {
        $ultimocredito = DB::select('select * from tblcreditosempleado_det where id_credito = ? and estado = "A" or "C" or "P";', [$idpres]);
        return collect($ultimocredito);
    }

    public function obtenerPrestEmpEnc($estado)
    {
        $ultimocredito = DB::select('select tblcreditos_empleado.id,
    tblcreditos_empleado.id_empleado,
    CONCAT(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) AS Nombre,
    tblcreditos_empleado.idcoordinador,
    tblcreditos_empleado.idtasa,
    tbltipo_tasa.nombre as tasa,
    tbltipo_tasa.tipo as tipoTasa,
    tbltipo_tasa.porcentaje,
    tblcreditos_empleado.estado_cuenta,
    tblcreditos_empleado.estado_cuenta_status,
    tblcreditos_empleado.factura,
    tblcreditos_empleado.factura_status,
    tblcreditos_empleado.pagare,
    tblcreditos_empleado.pagare_status,
    tblcreditos_empleado.status_entrega_estado_cuenta,
    tblcreditos_empleado.status_entrega_pagare,
    tblcreditos_empleado.status_entrega_factura,
    tblcreditos_empleado.tipo_credito,
    tblcreditos_empleado.estado,
    tblcreditos_empleado.comentario,
    tblcreditos_empleado.id_cuenta,
    tblcreditos_empleado.status_comentario,
    tblcreditos_empleado.monto,
    tblcreditos_empleado.interes,
    tblcreditos_empleado.ivainteres,
    tblcreditos_empleado.total,
    DATE_FORMAT(tblcreditos_empleado.fecha_inicio, "%d/%m/%Y") as fecha_inicio,
    DATE_FORMAT(tblcreditos_empleado.fecha_fin, "%d/%m/%Y") as fecha_fin,
    tblcreditos_empleado.tipo_plazo,
    tblcreditos_empleado.plazos,
    tblcreditos_empleado.interesredondeado,
    tblcreditos_empleado.totalredondeado,
    tblcreditos_empleado.created_at as created_atEnc
    from tblcreditos_empleado
    inner join tblempleados on tblempleados.id = tblcreditos_empleado.id_empleado
    inner join tbltipo_tasa on tbltipo_tasa.id = tblcreditos_empleado.idtasa
    where tblcreditos_empleado.estado = "' . $estado .
            '" ORDER BY tblcreditos_empleado.id DESC;');
        return collect($ultimocredito);
    }

    public function obtenerPrestEmpEncCompleto()
    {
        $ultimocredito = DB::select('select tblcreditos_empleado.id as id_credito,
    tblcreditos_empleado.id_empleado,
    CONCAT(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) AS Nombre,
    tblempleados.telefono as empleadoTel,
    CONCAT(tblempleados.colonia," ",tblempleados.calle," No. Int ",tblempleados.numero_interior," No. Ext ",tblempleados.numero_exterior,",  ",tblempleados.codigo_postal) AS direccionEmp,
    CONCAT(tblestados.nombre,", ",tblciudades.nombre )as ciudadEmp,
    tblcreditos_empleado.idcoordinador,
    tblcoordinadores.telefono as coordinadorTel,
    CONCAT(tblcoordinadores.primer_nombre," ",tblcoordinadores.segundo_nombre," ",tblcoordinadores.apellido_paterno," ",tblcoordinadores.apellido_materno) AS coordinador,
    tbltipo_tasa.nombre as tasa,
    tbltipo_tasa.tipo as tipoTasa,
    tbltipo_tasa.porcentaje,
    tblcreditos_empleado.tipo_credito,
    tblcreditos_empleado.estado as estadoPres,
    tblcreditos_empleado.monto,
    tblcreditos_empleado.interes,
    tblcreditos_empleado.ivainteres,
    tblcreditos_empleado.otros,
    tblcreditos_empleado.total,
    tblcreditos_empleado.fecha_inicio,
    tblcreditos_empleado.fecha_fin,
    tblcreditos_empleado.tipo_plazo,
    tblcreditos_empleado.plazos,
    tblcreditos_empleado.interesredondeado,
    tblcreditos_empleado.totalredondeado,
    tblcreditos_empleado.created_at as created_atEnc,

    tblcreditosempleado_det.id as id_credDet,
    tblcreditosempleado_det.estado,
    tblcreditosempleado_det.plazo,
    tblcreditosempleado_det.pago_quincenal,
    tblcreditosempleado_det.fecha_pago,
    tblcreditosempleado_det.otrosconceptos1,
    tblcreditosempleado_det.otrosconceptos2,
    tblcreditosempleado_det.otrosconceptos3,
    tblcreditosempleado_det.monto,
    tblcreditosempleado_det.pago_total,
    tblcreditosempleado_det.saldo_nuevo,
    tblcreditosempleado_det.fecha_saldado,
    tblcreditosempleado_det.created_at as created_atDet,
    tblcreditosempleado_det.updated_at as updated_atDet
    from tblcreditos_empleado
    inner join tblempleados on tblempleados.id = tblcreditos_empleado.id_empleado
    inner join tblciudades on tblciudades.id = tblempleados.idciudad
    inner join tblestados on tblestados.id = tblciudades.idestado
    inner join tbltipo_tasa on tbltipo_tasa.id = tblcreditos_empleado.idtasa
    inner join tblcreditosempleado_det on tblcreditos_empleado.id = tblcreditosempleado_det.id_credito
    inner join tblcoordinadores on tblcreditos_empleado.idcoordinador = tblcoordinadores.id;');
        return collect($ultimocredito);
    }

    public function ExcelPrestEmpEnc()
    {
        $ultimocredito = DB::select('select tblcreditos_empleado.id as id_cred,
    tblcreditos_empleado.id_empleado,
    CONCAT(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) AS Nombre,
    case
    when tblcreditos_empleado.estado = "A" then "ACTIVO" 
    when tblcreditos_empleado.estado = "S" then "SALDADO" 
    when tblcreditos_empleado.estado = "C" then "CANCELADO" END
    as  estado,
    tblcreditos_empleado.fecha_inicio,
    tblcreditos_empleado.fecha_fin,
    tblcreditos_empleado.tipo_credito,
    tblcreditos_empleado.plazos,
    (select pago_quincenal from tblcreditosempleado_det where id_credito = id_cred limit 1) pago_quincenal,
    tblcreditos_empleado.monto,
    tbltipo_tasa.nombre as tasa,
    tbltipo_tasa.porcentaje,
    tblcreditos_empleado.interes,
    tblcreditos_empleado.ivainteres,
    tblcreditos_empleado.total,
    tblcreditos_empleado.interesredondeado,
    tblcreditos_empleado.totalredondeado,

    case
    when tblcreditos_empleado.estado_cuenta_status = "A" then "SI" 
    else "NO" end as  estado_cuenta_status,
    case
    when tblcreditos_empleado.pagare_status = "A" then "SI" 
    else "NO" end as  pagare_status,
    case
    when tblcreditos_empleado.factura_status = "A" then "SI" 
    else "NO" end as  factura_status,


    case
    when tblcreditos_empleado.status_entrega_estado_cuenta = "entregado" then "SI" 
    else "NO" end as  status_entrega_estado_cuenta,
    case
    when tblcreditos_empleado.status_entrega_pagare = "entregado" then "SI" 
    else "NO" end as  status_entrega_pagare,
    case
    when tblcreditos_empleado.status_entrega_factura = "entregado" then "SI" 
    else "NO" end as  status_entrega_factura,
    

    CONCAT(tblcoordinadores.primer_nombre," ",tblcoordinadores.segundo_nombre," ",tblcoordinadores.apellido_paterno," ",tblcoordinadores.apellido_materno) AS NombreCoordinador,
    tblcreditos_empleado.comentario,
    tblcreditos_empleado.created_at as created_atEnc
    from tblcreditos_empleado
    inner join tblempleados on tblempleados.id = tblcreditos_empleado.id_empleado
    inner join tbltipo_tasa on tbltipo_tasa.id = tblcreditos_empleado.idtasa
    inner join tblcoordinadores on tblcoordinadores.id = tblcreditos_empleado.idcoordinador
    where tblcreditos_empleado.estado <> "P" 
    ORDER BY tblcreditos_empleado.id DESC;');
        return collect($ultimocredito);
    }

    public function ExcelTempPrestEmpEnc()
    {
        $ultimocredito = DB::select('select temptblcreditos_empleado.id,
    temptblcreditos_empleado.id_empleado,
    CONCAT(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) AS Nombre,
    temptblcreditos_empleado.fecha_inicio,
    temptblcreditos_empleado.tipo_credito,
    temptblcreditos_empleado.tipo_plazo,
    temptblcreditos_empleado.plazos,
    temptblcreditos_empleado.monto,
    tbltipo_tasa.nombre as tasa,
    tbltipo_tasa.porcentaje,
    temptblcreditos_empleado.interes,
    temptblcreditos_empleado.ivainteres,
    temptblcreditos_empleado.total,
    temptblcreditos_empleado.interesredondeado,
    temptblcreditos_empleado.totalredondeado,
    CONCAT(tblcoordinadores.primer_nombre," ",tblcoordinadores.segundo_nombre," ",tblcoordinadores.apellido_paterno," ",tblcoordinadores.apellido_materno) AS NombreCoordinador,
    temptblcreditos_empleado.comentario,
    temptblcreditos_empleado.created_at as created_atEnc
    from temptblcreditos_empleado
    inner join tblempleados on tblempleados.id = temptblcreditos_empleado.id_empleado
    inner join tbltipo_tasa on tbltipo_tasa.id = temptblcreditos_empleado.idtasa
    inner join tblcoordinadores on tblcoordinadores.id = temptblcreditos_empleado.idcoordinador
    where temptblcreditos_empleado.estado = "P" 
    ORDER BY temptblcreditos_empleado.id ASC;');
        return collect($ultimocredito);
    }

    public function obtenerTempPrestEmpEnc()
    {
        $ultimocredito = DB::select('select temptblcreditos_empleado.id,
    temptblcreditos_empleado.id_empleado,
    CONCAT(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) AS Nombre,
    temptblcreditos_empleado.idcoordinador,
    temptblcreditos_empleado.idtasa,
    tbltipo_tasa.nombre as tasa,
    tbltipo_tasa.tipo as tipoTasa,
    tbltipo_tasa.porcentaje,
    temptblcreditos_empleado.tipo_cuenta,
    CASE 
        WHEN temptblcreditos_empleado.tipo_cuenta = "CUENTA" then tblcuentas.nombre
        ELSE tblcajas.nombre
    END as nombre_cuenta,
    CASE 
        WHEN temptblcreditos_empleado.tipo_cuenta = "CUENTA" then tblcuentas.id
        ELSE tblcajas.id
    END as id_cuenta,
    temptblcreditos_empleado.tipo_credito,
    temptblcreditos_empleado.estado,
    temptblcreditos_empleado.comentario,
    temptblcreditos_empleado.status_comentario,
    temptblcreditos_empleado.monto,
    temptblcreditos_empleado.interes,
    temptblcreditos_empleado.ivainteres,
    temptblcreditos_empleado.total,
    DATE_FORMAT(temptblcreditos_empleado.fecha_inicio, "%d/%m/%Y") as fecha_inicio,
    temptblcreditos_empleado.fecha_fin,
    temptblcreditos_empleado.tipo_plazo,
    temptblcreditos_empleado.plazos,
    temptblcreditos_empleado.interesredondeado,
    temptblcreditos_empleado.totalredondeado,
    temptblcreditos_empleado.estado_cuenta,
    temptblcreditos_empleado.pagare,
    temptblcreditos_empleado.factura,
    temptblcreditos_empleado.created_at as created_atEnc
    from temptblcreditos_empleado
    inner join tblempleados on tblempleados.id = temptblcreditos_empleado.id_empleado
    inner join tbltipo_tasa on tbltipo_tasa.id = temptblcreditos_empleado.idtasa
    LEFT JOIN tblcuentas  on tblcuentas.id = temptblcreditos_empleado.id_cuenta 
        AND temptblcreditos_empleado.tipo_cuenta = "CUENTA"
    LEFT JOIN tblcajas ON tblcajas.id = temptblcreditos_empleado.id_caja
        AND temptblcreditos_empleado.tipo_cuenta = "CAJA"
    where temptblcreditos_empleado.estado = "P" 
    ORDER BY temptblcreditos_empleado.id DESC;');
        return collect($ultimocredito);
    }

    public function obtenerTempPrestEmpDet(int $id)
    {
        $ultimocredito = DB::select('select temptblcreditos_empleado.id as id_credito,
    temptblcreditos_empleado.id_empleado,
    CONCAT(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) AS Nombre,
    tblempleados.telefono as empleadoTel,
    CONCAT(tblempleados.colonia," ",tblempleados.calle," No. Int ",tblempleados.numero_interior," No. Ext ",tblempleados.numero_exterior,",  ",tblempleados.codigo_postal) AS direccionEmp,
    CONCAT(tblestados.nombre,", ",tblciudades.nombre )as ciudadEmp,
    temptblcreditos_empleado.idcoordinador,
    tblcoordinadores.telefono as coordinadorTel,
    CONCAT(tblcoordinadores.primer_nombre," ",tblcoordinadores.segundo_nombre," ",tblcoordinadores.apellido_paterno," ",tblcoordinadores.apellido_materno) AS coordinador,
    tbltipo_tasa.nombre as tasa,
    tbltipo_tasa.tipo as tipoTasa,
    tbltipo_tasa.porcentaje,
    temptblcreditos_empleado.tipo_credito,
    temptblcreditos_empleado.estado as estadoPres,
    temptblcreditos_empleado.monto,
    temptblcreditos_empleado.interes,
    temptblcreditos_empleado.ivainteres,
    temptblcreditos_empleado.otros,
    temptblcreditos_empleado.total,
    DATE_FORMAT(temptblcreditos_empleado.fecha_inicio, "%d/%m/%Y") as fecha_inicio,
    DATE_FORMAT(temptblcreditos_empleado.fecha_fin, "%d/%m/%Y") as fecha_fin,
    temptblcreditos_empleado.tipo_plazo,
    temptblcreditos_empleado.plazos,
    temptblcreditos_empleado.interesredondeado,
    temptblcreditos_empleado.totalredondeado,
    temptblcreditos_empleado.created_at as created_atEnc,
    temptblcreditos_empleado_det.id as id_credDet,
    temptblcreditos_empleado_det.estado,
    temptblcreditos_empleado_det.plazo,
    temptblcreditos_empleado_det.pago_quincenal,
    #DATE_FORMAT(temptblcreditos_empleado_det.fecha_pago, "%d/%m/%Y") as fecha_pago,
    temptblcreditos_empleado_det.fecha_pago,
    temptblcreditos_empleado_det.otrosconceptos1,
    temptblcreditos_empleado_det.otrosconceptos2,
    temptblcreditos_empleado_det.otrosconceptos3,
    temptblcreditos_empleado_det.monto,
    temptblcreditos_empleado_det.pago_total,
    temptblcreditos_empleado_det.saldo_nuevo,
    DATE_FORMAT(temptblcreditos_empleado_det.fecha_saldado, "%d/%m/%Y") as fecha_saldado,
    temptblcreditos_empleado_det.created_at as created_atDet,
    temptblcreditos_empleado_det.updated_at as updated_atDet
    from temptblcreditos_empleado
    inner join tblempleados on tblempleados.id = temptblcreditos_empleado.id_empleado
    inner join tblciudades on tblciudades.id = tblempleados.idciudad
    inner join tblestados on tblestados.id = tblciudades.idestado
    inner join tbltipo_tasa on tbltipo_tasa.id = temptblcreditos_empleado.idtasa
    inner join temptblcreditos_empleado_det on temptblcreditos_empleado.id = temptblcreditos_empleado_det.id_credito
    inner join tblcoordinadores on temptblcreditos_empleado.idcoordinador = tblcoordinadores.id
    where temptblcreditos_empleado.id = ?;', [$id]);
        return collect($ultimocredito);
    }

    public function obtenerPrestEmpDet(int $id)
    {
        $ultimocredito = DB::select('select tblcreditos_empleado.id as id_credito,
        tblcreditos_empleado.id_empleado,
        CONCAT(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) AS Nombre,
        tblempleados.telefono as empleadoTel,
        CONCAT(tblempleados.colonia," ",tblempleados.calle," No. Int ",tblempleados.numero_interior," No. Ext ",tblempleados.numero_exterior,",  ",tblempleados.codigo_postal) AS direccionEmp,
        CONCAT(tblestados.nombre,", ",tblciudades.nombre )as ciudadEmp,
        tblcreditos_empleado.idcoordinador,
        tblcoordinadores.telefono as coordinadorTel,
        CONCAT(tblcoordinadores.primer_nombre," ",tblcoordinadores.segundo_nombre," ",tblcoordinadores.apellido_paterno," ",tblcoordinadores.apellido_materno) AS coordinador,
        tbltipo_tasa.nombre as tasa,
        tbltipo_tasa.tipo as tipoTasa,
        tbltipo_tasa.porcentaje,
        tblcreditos_empleado.tipo_credito,
        tblcreditos_empleado.estado as estadoPres,
        tblcreditos_empleado.monto,
        tblcreditos_empleado.interes,
        tblcreditos_empleado.ivainteres,
        tblcreditos_empleado.otros,
        tblcreditos_empleado.total,
        DATE_FORMAT(tblcreditos_empleado.fecha_inicio, "%d/%m/%Y") as fecha_inicio,
        DATE_FORMAT(tblcreditos_empleado.fecha_fin, "%d/%m/%Y") as fecha_fin,
        tblcreditos_empleado.tipo_plazo,
        tblcreditos_empleado.plazos,
        tblcreditos_empleado.interesredondeado,
        tblcreditos_empleado.totalredondeado,
        tblcreditos_empleado.created_at as created_atEnc,
        tblcreditosempleado_det.id as id_credDet,
        tblcreditosempleado_det.estado,
        tblcreditosempleado_det.plazo,
        tblcreditosempleado_det.pago_quincenal,
        DATE_FORMAT(tblcreditosempleado_det.fecha_pago, "%d/%m/%Y") as fecha_pago,
        tblcreditosempleado_det.otrosconceptos1,
        tblcreditosempleado_det.otrosconceptos2,
        tblcreditosempleado_det.otrosconceptos3,
        tblcreditosempleado_det.monto,
        tblcreditosempleado_det.pago_total,
        tblcreditosempleado_det.saldo_nuevo,
        DATE_FORMAT(tblcreditosempleado_det.fecha_saldado, "%d/%m/%Y") as fecha_saldado,
        tblcreditosempleado_det.created_at as created_atDet,
        tblcreditosempleado_det.updated_at as updated_atDet
        from tblcreditos_empleado
        inner join tblempleados on tblempleados.id = tblcreditos_empleado.id_empleado
        inner join tblciudades on tblciudades.id = tblempleados.idciudad
        inner join tblestados on tblestados.id = tblciudades.idestado
        inner join tbltipo_tasa on tbltipo_tasa.id = tblcreditos_empleado.idtasa
        inner join tblcreditosempleado_det on tblcreditos_empleado.id = tblcreditosempleado_det.id_credito
        inner join tblcoordinadores on tblcreditos_empleado.idcoordinador = tblcoordinadores.id
        where tblcreditos_empleado.id = ?;', [$id]);
      
        return collect($ultimocredito);
    }

    public function obtenerPrestEmpTemp(int $id)
    {
        $ultimocredito = DB::select('select *
    from temptblcreditos_empleado
    inner join tblempleados on tblempleados.id = temptblcreditos_empleado.id_empleado
    inner join tbltipo_tasa on tbltipo_tasa.id = temptblcreditos_empleado.idtasa
    inner join temptblcreditos_empleado_det on temptblcreditos_empleado.id = temptblcreditos_empleado_det.id_credito
    where temptblcreditos_empleado_det.estado = "P" and tblempleados.id = ?;', [$id]);
        return collect($ultimocredito);
    }

    public function obtenerPrestEmp(int $id)
    {
        $ultimocredito = DB::select('select *
    from tblcreditos_empleado
    where tblcreditos_empleado.estado = "A" and	tblcreditos_empleado.id_empleado = ?;', [$id]);
        return collect($ultimocredito);
    }

    public function validaPrestEmpTemp(int $id, string $permiso, string $tipocredito)
    {

        if ($permiso == "si") {
            $ultimocredito = DB::select('select temptblcreditos_empleado.tipo_credito
        from temptblcreditos_empleado
        where temptblcreditos_empleado.id_empleado = ? and temptblcreditos_empleado.estado = "P" and temptblcreditos_empleado.tipo_credito = ?;', [$id, $tipocredito]);
        } else {

            $ultimocredito = DB::select('select *
        from temptblcreditos_empleado
        where temptblcreditos_empleado.estado = "P" and temptblcreditos_empleado.id_empleado = ?;', [$id]);

        }


        return collect($ultimocredito);
    }

    public function validaPrestEmp(int $id, string $permiso, string $tipocredito)
    {

        if ($permiso == "si") {

            $ultimocredito = DB::select('select tblcreditos_empleado.tipo_credito
        from tblcreditos_empleado
        where tblcreditos_empleado.id_empleado = ? and tblcreditos_empleado.estado = "A" and tblcreditos_empleado.tipo_credito = ?;', [$id, $tipocredito]);
        } else {
            $ultimocredito = DB::select('select *
        from tblcreditos_empleado
        where tblcreditos_empleado.estado = "A" and	tblcreditos_empleado.id_empleado = ?;', [$id]);

        }

        return collect($ultimocredito);
    }

    public function obtenernoinasEnc()
    {

        $sql = DB::select("select tblnominas_pagoenc.fecha_inicio, tblnominas_pagoenc.fecha_fin from tblnominas_pagoenc;");
        return collect($sql);
    }

    public function obtenerprorrateo(){
        $conceptospro = DB::select('select * from tblconceptos');
        return collect($conceptospro);
    }

    public function obtenerprestmoempxfecha(string $fecha_inicio, string $fecha_fin)
    {

        $sql = DB::select("select a.id, a.id_credito, b.id_empleado, a.fecha_pago, 
    a.pago_quincenal, a.estado, b.id_cuenta 
    from tblcreditosempleado_det  a
    inner join tblcreditos_empleado b on a.id_credito = b.id
    where a.estado <> 'S' and a.fecha_pago between ? and ?;", [$fecha_inicio, $fecha_fin]);
        return collect($sql);

    }

    public function obtenerprestmoempxfechaRevertirPago(string $fecha_inicio, string $fecha_fin){
        $sql = DB::select("SELECT a.id, a.id_credito, b.id_empleado, a.fecha_pago, 
            a.pago_quincenal, a.estado, b.id_cuenta 
            FROM tblcreditosempleado_det  a
            INNER JOIN tblcreditos_empleado b on a.id_credito = b.id
            WHERE a.estado <> 'A' and a.fecha_pago between ? and ?;",[$fecha_inicio, $fecha_fin]);
        return collect($sql);
    }

    public function obtenersaldoactualpacoma()
    {

        $sql = DB::select("select id,saldo_actual from tblcuentas where id = 19;");
        return collect($sql);
    }

    public function obtenerultimopagnom()
    {
        $varultimoempleado = DB::select("select id from tblpagosnomenc order by id desc limit 1");
        return collect($varultimoempleado);
    }

    public function obtenerdebeprestamo(int $id)
    {

        $sql = DB::select("select sum(b.pago_quincenal) as monto_debe 
    from tblcreditos_empleado a
    inner join tblcreditosempleado_det b on a.id = b.id_credito
    where b.estado = 'A' and a.id_empleado = ?;", [$id]);
        return collect($sql);
    }

    public function obtenerpagodetxconcepto(int $id)
    {

        $sql = DB::select("select 
    idempleado ,total_apagar_excedente, total_nomina_fiscal, total_efectivo
    from tblnominas_pagodet 
    where tblnominas_pagodet.idpagonomina = ?;", [$id]);
        return collect($sql);
    }

    public function obtenerpagodetsum(int $id, int $idempleado)
    {

        $sql = DB::select("select 
    idempleado,
    sum(total_apagar_excedente + total_nomina_fiscal + total_efectivo) as total_pagar
    from tblnominas_pagodet 
    where tblnominas_pagodet.idpagonomina = ? and idempleado = ?;", [$id, $idempleado]);
        return collect($sql);
    }

    public function globaldetprestnom()
    {
        $sql = DB::select("SELECT sum(totalredondeado) as capital_general,count(id)AS clientes_activos,(totalredondeado)-(select SUM(totalredondeado)-(SELECT sum(pago_quincenal) FROM tblcreditosempleado_det WHERE ESTADO = 'a') as saldo  FROM  tblcreditos_empleado) as saldo_actual,
    (select SUM(pago_quincenal)AS saldo_atrasado from tblcreditosempleado_det where estado ='P' AND fecha_pago < now())as saldo_atrasado FROM  tblcreditos_empleado 
    where estado= 'A';");
        return collect($sql);
    }



    public function tptresumenretenciones(string $fechaquincena)
    {
        $fechaquincena2 = $fechaquincena;
        $sql = DB::select(
            "select 
    suc.nombre as nombre,
    FORMAT(sum(sueldo_fiscal),2) AS sueldo_fiscal,
    FORMAT(sum(deudores_fiscal),2) AS deudores_fiscal,
    FORMAT(sum(pago_infonavit),2) AS pago_infonavit,
    FORMAT(sum(pago_imss),2) AS pago_imss,
    FORMAT(sum(pago_isr),2) AS pago_isr ,
    FORMAT(sum(total_nomina_fiscal),2) AS total_nomina_fiscal
    from tblnominas_pagoenc enc
    inner join tblnominas_pagodet det on det.idpagonomina = enc.id
    inner join tblempleados emp on det.idempleado = emp.id
    inner join tblsucursales suc on emp.idsucursal = suc.id
    where enc.fecha_inicio = ?
    group by emp.idsucursal
    union 
    select 
	'TOTAL GENERAL' as nombre,
    FORMAT(sum(sueldo_fiscal),2) AS sueldo_fiscal,
    FORMAT(sum(deudores_fiscal),2) AS deudores_fiscal,
    FORMAT(sum(pago_infonavit),2) AS pago_infonavit,
    FORMAT(sum(pago_imss),2) AS pago_imss,
    FORMAT(sum(pago_isr),2) AS pago_isr,
    FORMAT(sum(total_nomina_fiscal),2) AS total_nomina_fiscal
    from tblnominas_pagoenc enc
    inner join tblnominas_pagodet det on det.idpagonomina = enc.id
    inner join tblempleados emp on det.idempleado = emp.id
    inner join tblsucursales suc on emp.idsucursal = suc.id
     where enc.fecha_inicio = ?",
            [$fechaquincena, $fechaquincena2]
        );
        return collect($sql);
    }


    // public function rptenomporsucursal(string $fechaquincena){
//     $sql = DB::select("select 
//     enc.id,
//     suc.id as idsucursal,
//     suc.nombre as sucursal,
//     case
//     when emp.idsucursal = 15 then 0 else sum(total_nomina_fiscal) end as  nomina_fiscal,
//     case
//     when emp.idsucursal = 2 ||  emp.idsucursal = 6 || emp.idsucursal = 3 ||  emp.idsucursal = 9   then 0 else sum(total_apagar_excedente)+(total_efectivo) end as  pagonomina_excedente,
//     case
//     when emp.idsucursal = 2 ||  emp.idsucursal = 6 || emp.idsucursal = 3 ||  emp.idsucursal = 9   then sum(total_apagar_excedente)+(total_efectivo) else 0 end as  efectivo_cajas,
//     sum(total_nomina_fiscal)+sum(total_apagar_excedente)+(total_efectivo) as Total
//     from tblnominas_pagoenc enc
//     inner join tblnominas_pagodet det on det.idpagonomina = enc.id
//     inner join tblempleados emp on det.idempleado = emp.id
//     inner join tblsucursales suc on emp.idsucursal = suc.id
//      where enc.fecha_inicio = ?
//      group by emp.idsucursal ",[$fechaquincena]);
//     return collect($sql);
// }


    public function rptenomporsucursal(int $idnomina)
    {
        $sql = DB::select("SELECT  tblsucursales.id as idsucursal, tblsucursales.nombre as sucursal, tblnominas_pagodet.idpagonomina,
    sum(tblnominas_pagodet.total_nomina_fiscal) as nomina_fiscal ,
    sum(tblnominas_pagodet.total_apagar_excedente) as pagonomina_excedente ,
    sum(tblnominas_pagodet.total_efectivo) as efectivo_cajas,
    sum(tblnominas_pagodet.total_nomina_fiscal)  + sum(tblnominas_pagodet.total_apagar_excedente)  + sum(tblnominas_pagodet.total_efectivo) as Total
    FROM tblnominas_pagodet
    INNER JOIN tblempleados on tblempleados.id = tblnominas_pagodet.idempleado
    INNER JOIN tblsucursales on tblsucursales.id = tblempleados.idsucursal
    WHERE tblnominas_pagodet.idpagonomina = ? GROUP by tblempleados.idsucursal;", [$idnomina]);
        return collect($sql);
    }

    public function rptexporexcelniomxsuc(int $id)
    {
        $id2 = $id;
        $sql = DB::select("select nombre,
    format(nomina_fiscal,2),
    format(pagonomina_excedente,2),
    format(efectivo_cajas,2),
    format(total,2) 
    from temprptnomxsuc
    where idnomina  = ?
    union
    select 'TOTAL GENERAL' AS nombre, 
    format(SUM(nomina_fiscal),2) AS nomina_fiscal, 
    format(SUM(pagonomina_excedente),2) AS pagonomina_excedente,
    format(SUM(efectivo_cajas),2) AS efectivo_cajas,
    format(SUM(total),2) AS total from temprptnomxsuc
    where idnomina = ? ;", [$id, $id2]);
        return collect($sql);

    }

    public function recibonomina($id)
    {
        $varempl = DB::select('
        select 
		tblempleados.id as id_empleado,
        case 
        when tblempleados.segundo_nombre = " " 
        then CONCAT (tblempleados.primer_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno ) 
        else
        CONCAT (tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno ) 
        end as NOMBRE,
		
		tblnominas_pagodet.idpagonomina,
        tblnominas_pagodet.id,
        tblsucursales.nombre as sucursal,
        tblpuestos.nombre as puesto,
     
        tblempleados.nss,
        tblempleados.rfc,
        tblempleados.curp,
        tblempleados.fecha_ingreso,
        tblnominas.fecha_ingreso_imss,
        tblempleados.codigo_postal as domicilio_fiscal,
        tblnominas.salario_fijo as salario_diario,
        tblnominas_pagodet.salario_diario_integrado,
        tblnominas.excedente as salario_excedente,
        tblnominas_pagodet.dias_laborados,
        tblnominas_pagodet.sueldo_fiscal,
        tblnominas_pagodet.sueldo_excedente,
        tblnominas_pagodet.total_sueldo,
        
        tblnominas_pagodet.faltas_reta_aus,
        tblnominas_pagodet.total_faltas_reta_aus,
        
        tblnominas_pagodet.horas_extras,
        tblnominas_pagodet.horas_extras_pago_f,
        tblnominas_pagodet.horas_extras_pago_e,
        tblnominas_pagodet.total_horas_extras,
        
        tblnominas_pagodet.pago_infonavit,
        tblnominas_pagodet.pago_imss,
        tblnominas_pagodet.pago_subsidio,
        tblnominas_pagodet.pago_isr,
        tblnominas_pagodet.fonacot,

        tblnominas_pagodet.deudores_no_fiscal ,
        tblnominas_pagodet.deudores_fiscal,
        tblnominas_pagodet.total_deudores,

        tblnominas_pagodet.otros,
        tblnominas_pagodet.despensa,
        tblnominas_pagodet.percepcion_extraordinaria,

        tblnominas_pagodet.dias_vaciones,
        tblnominas_pagodet.pago_dias_vacaciones_fis,
        tblnominas_pagodet.pago_dias_vacaciones_exce,
        tblnominas_pagodet.pago_dias_vacaciones,

        tblnominas_pagodet.pago_prima_vacacional_fis,
        tblnominas_pagodet.pago_prima_vacacional_exce,
        tblnominas_pagodet.pago_prima_vacacional,

        tblnominas_pagodet.total_nomina_fiscal,
        tblnominas_pagodet.total_apagar_excedente,
        tblnominas_pagodet.total_apagar,
        tblbancos.nombre as banco,
        tblnominas.numero_cuenta,

       
        tblnominas_pagoenc.fecha_fin,
        tblnominas_pagoenc.fecha_inicio,

        tblnominas_pagodet.dias_descanso,
        tblnominas_pagodet.dias_prima_dominical,
        tblnominas_pagodet.pago_prima_dominical,
        tblnominas_pagodet.pago_dias_descanso,
        tbltipo_nominas.tipo as frecuencia_pago

        from tblnominas_pagoenc 
        inner join tblnominas_pagodet on tblnominas_pagoenc.id = tblnominas_pagodet.idpagonomina
        inner join tblempleados on  tblempleados.id = tblnominas_pagodet.idempleado
        inner join tblnominas on tblnominas.idempleado = tblempleados.id 
        inner join tblsucursales on  tblsucursales.id = tblempleados.idsucursal
        inner join tblpuestos on  tblpuestos.id = tblempleados.idpuesto
		inner join tblbancos on  tblbancos.id = tblnominas.idbancos 
        inner join tbltipo_nominas  on tblnominas_pagoenc.idtiponomina = tbltipo_nominas.id
        where tblnominas_pagodet.idpagonomina = ? ORDER BY tblempleados.id ASC;', [$id]);
        return collect($varempl);
    }

    /**
     * Arma el Listado de Nómina PDF.
     * $modo: completo | fiscal | excedente
     * $orden: apellido | id
     */
    public function buildListadoNominaData(int $idpagonomina, string $modo = 'completo', string $orden = 'apellido')
    {
        $modo = in_array($modo, ['completo', 'fiscal', 'excedente'], true) ? $modo : 'completo';
        $orden = in_array($orden, ['apellido', 'id'], true) ? $orden : 'apellido';

        $varnominas = $this->obtenernominasporid($idpagonomina);
        $tipoMap = [1 => 'SEMANAL', 2 => 'QUINCENAL', 3 => 'MENSUAL'];

        $empleados = $varnominas->map(function ($n) use ($modo) {
            $conceptos = $this->conceptosListadoNominaEmpleado($n, $modo);

            $totalPercepciones = round(collect($conceptos)->where('tipo', 'percepcion')->sum('monto'), 2);
            $totalRetenciones = round(collect($conceptos)->where('tipo', 'retencion')->sum('monto'), 2);
            $pagoEfectivo = round($totalPercepciones - $totalRetenciones, 2);

            $sueldoDiario = $modo === 'excedente'
                ? (float) ($n->excedente ?? 0)
                : ($modo === 'fiscal'
                    ? (float) ($n->salario_fijo ?? 0)
                    : (float) ($n->salario_fijo ?? 0) + (float) ($n->excedente ?? 0));

            $apellidos = trim(($n->apellido_paterno ?? '') . ' ' . ($n->apellido_materno ?? ''));
            $nombres = trim(($n->primer_nombre ?? '') . ' ' . ($n->segundo_nombre ?? ''));
            $nombreListado = mb_strtoupper(trim($apellidos . ', ' . $nombres), 'UTF-8');

            return (object) [
                'idempleado' => $n->idempleado,
                'apellido_paterno' => $n->apellido_paterno ?? '',
                'apellido_materno' => $n->apellido_materno ?? '',
                'nombre_listado' => $nombreListado,
                'sueldo_diario' => $sueldoDiario,
                'percepciones' => $totalPercepciones,
                'retenciones' => $totalRetenciones,
                'pago_especie' => 0,
                'pago_efectivo' => $pagoEfectivo,
                'conceptos' => $conceptos,
            ];
        })->filter(function ($e) {
            return $e->percepciones > 0 || $e->retenciones > 0 || $e->pago_efectivo != 0;
        })->values();

        if ($orden === 'apellido') {
            $empleados = $empleados->sortBy(function ($e) {
                return mb_strtoupper(($e->apellido_paterno ?? '') . ' ' . ($e->apellido_materno ?? '') . ' ' . ($e->idempleado ?? ''), 'UTF-8');
            }, SORT_NATURAL)->values();
        } else {
            $empleados = $empleados->sortBy('idempleado')->values();
        }

        $first = $varnominas->first();
        $idTipo = (int) ($first->idtiponomina ?? 0);

        return [
            'empleados' => $empleados,
            'fecha_inicio' => $first->fecha_inicio ?? null,
            'fecha_fin' => $first->fecha_fin ?? null,
            'tipo_nomina' => $tipoMap[$idTipo] ?? 'NÓMINA',
            'nombre_nomina' => $first->nombre_nomina ?? '',
            'modo' => $modo,
            'orden' => $orden,
            'totales' => [
                'percepciones' => round($empleados->sum('percepciones'), 2),
                'retenciones' => round($empleados->sum('retenciones'), 2),
                'pago_especie' => 0,
                'pago_efectivo' => round($empleados->sum('pago_efectivo'), 2),
            ],
        ];
    }

    protected function conceptosListadoNominaEmpleado($n, string $modo): array
    {
        $incluirFiscal = in_array($modo, ['completo', 'fiscal'], true);
        $incluirExcedente = in_array($modo, ['completo', 'excedente'], true);
        $conceptos = [];

        $add = function (string $tipo, string $codigo, string $descripcion, $monto, $unidad = null) use (&$conceptos) {
            $monto = round((float) $monto, 2);
            if (abs($monto) < 0.005) {
                return;
            }
            $conceptos[] = [
                'tipo' => $tipo,
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'monto' => $monto,
                'unidad' => $unidad,
            ];
        };

        if ($incluirFiscal) {
            $add('percepcion', '101', 'Sueldo normal', $n->sueldo_fiscal ?? 0, number_format((float) ($n->dias_laborados ?? 0), 2) . ' días');
            $add('percepcion', '019', 'Horas extras', $n->horas_extras_pago_f ?? 0, number_format((float) ($n->horas_extras ?? 0), 2) . ' horas');
            $add('percepcion', '103', 'Vacaciones', $n->pago_dias_vacaciones_fis ?? 0, number_format((float) ($n->dias_vaciones ?? 0), 2) . ' días');
            $add('percepcion', '1pv', 'Prima vacacional', $n->pago_prima_vacacional_fis ?? 0, number_format((float) ($n->dias_prima_vacacional ?? 0), 2) . ' días');
            $add('percepcion', '029', 'Despensa', $n->despensa ?? 0);
            $add('percepcion', '038', 'Percepción extraordinaria', $n->percepcion_extraordinaria ?? 0);
            $add('percepcion', '999', 'Otros', $n->otros ?? 0);
        }

        if ($incluirExcedente) {
            $add('percepcion', '101E', 'Sueldo excedente', $n->sueldo_excedente ?? 0, number_format((float) ($n->dias_laborados ?? 0), 2) . ' días');
            $add('percepcion', '019E', 'Horas extras excedente', $n->horas_extras_pago_e ?? 0, number_format((float) ($n->horas_extras ?? 0), 2) . ' horas');
            $add('percepcion', '102', 'Días de descanso', $n->pago_dias_descanso ?? 0, number_format((float) ($n->dias_descanso ?? 0), 2) . ' días');
            $add('percepcion', 'PD', 'Prima dominical', $n->pago_prima_dominical ?? 0, number_format((float) ($n->dias_prima_dominical ?? 0), 2) . ' días');
            if ($modo === 'excedente') {
                $add('percepcion', '103E', 'Vacaciones excedente', $n->pago_dias_vacaciones_exce ?? 0, number_format((float) ($n->dias_vaciones ?? 0), 2) . ' días');
                $add('percepcion', '1pvE', 'Prima vacacional excedente', $n->pago_prima_vacacional_exce ?? 0, number_format((float) ($n->dias_prima_vacacional ?? 0), 2) . ' días');
            } elseif ($modo === 'completo') {
                // En completo, vacaciones/prima ya se mostraron en fiscal por monto total si fis+exce están separados;
                // agregar solo la parte excedente para no omitirla.
                $add('percepcion', '103E', 'Vacaciones excedente', $n->pago_dias_vacaciones_exce ?? 0);
                $add('percepcion', '1pvE', 'Prima vacacional excedente', $n->pago_prima_vacacional_exce ?? 0);
            }
            $add('percepcion', 'BON', 'Bono', $n->bono ?? 0);
            $add('percepcion', 'VIA', 'Viáticos', $n->viaticos ?? 0);
        }

        if ($incluirFiscal || $modo === 'completo') {
            $add('retencion', '201', 'ISR', $n->pago_isr ?? 0);
            $add('retencion', '202', 'Seguro social', $n->pago_imss ?? 0);
            $add('retencion', 'CI', 'Ret Credito Infonavit', $n->pago_infonavit ?? 0);
            $add('retencion', 'FON', 'FONACOT', $n->fonacot ?? 0);
            $add('retencion', 'PRE', 'Prestamo de Empresa', ((float) ($n->deudores_fiscal ?? 0)) + ((float) ($n->deudores_no_fiscal ?? 0)));
        }

        return $conceptos;
    }

    public function recibonominaEmpleado($idpagodet)
    {
        $varempl = DB::select('
        select 
		tblempleados.id as id_empleado,
        case 
        when tblempleados.segundo_nombre = " " 
        then CONCAT (tblempleados.primer_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno ) 
        else
        CONCAT (tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno ) 
        end as NOMBRE,
		
		tblnominas_pagodet.idpagonomina,
        tblnominas_pagodet.id,
        tblsucursales.nombre as sucursal,
        tblpuestos.nombre as puesto,
     
        tblempleados.nss,
        tblempleados.rfc,
        tblempleados.curp,
        tblempleados.fecha_ingreso,
        tblnominas.fecha_ingreso_imss,
        tblempleados.codigo_postal as domicilio_fiscal,
        tblnominas.salario_fijo as salario_diario,
        tblnominas_pagodet.salario_diario_integrado,
        tblnominas.excedente as salario_excedente,
        tblnominas_pagodet.dias_laborados,
        tblnominas_pagodet.sueldo_fiscal,
        tblnominas_pagodet.sueldo_excedente,
        tblnominas_pagodet.total_sueldo,
        
        tblnominas_pagodet.faltas_reta_aus,
        tblnominas_pagodet.total_faltas_reta_aus,
        
        tblnominas_pagodet.horas_extras,
        tblnominas_pagodet.horas_extras_pago_f,
        tblnominas_pagodet.horas_extras_pago_e,
        tblnominas_pagodet.total_horas_extras,
        
        tblnominas_pagodet.pago_infonavit,
        tblnominas_pagodet.pago_imss,
        tblnominas_pagodet.pago_subsidio,
        tblnominas_pagodet.pago_isr,
        tblnominas_pagodet.fonacot,

        tblnominas_pagodet.deudores_no_fiscal ,
        tblnominas_pagodet.deudores_fiscal,
        tblnominas_pagodet.total_deudores,

        tblnominas_pagodet.otros,
        tblnominas_pagodet.despensa,
        tblnominas_pagodet.percepcion_extraordinaria,

        tblnominas_pagodet.dias_vaciones,
        tblnominas_pagodet.pago_dias_vacaciones_fis,
        tblnominas_pagodet.pago_dias_vacaciones_exce,
        tblnominas_pagodet.pago_dias_vacaciones,

        tblnominas_pagodet.pago_prima_vacacional_fis,
        tblnominas_pagodet.pago_prima_vacacional_exce,
        tblnominas_pagodet.pago_prima_vacacional,

        tblnominas_pagodet.total_nomina_fiscal,
        tblnominas_pagodet.total_apagar_excedente,
        tblnominas_pagodet.total_apagar,
        tblbancos.nombre as banco,
        tblnominas.numero_cuenta,

       
        tblnominas_pagoenc.fecha_fin,
        tblnominas_pagoenc.fecha_inicio,

        tblnominas_pagodet.dias_descanso,
        tblnominas_pagodet.dias_prima_dominical,
        tblnominas_pagodet.pago_prima_dominical,
        tblnominas_pagodet.pago_dias_descanso,
        tbltipo_nominas.tipo as frecuencia_pago

        from tblnominas_pagoenc 
        inner join tblnominas_pagodet on tblnominas_pagoenc.id = tblnominas_pagodet.idpagonomina
        inner join tblempleados on  tblempleados.id = tblnominas_pagodet.idempleado
        inner join tblnominas on tblnominas.idempleado = tblempleados.id 
        inner join tblsucursales on  tblsucursales.id = tblempleados.idsucursal
        inner join tblpuestos on  tblpuestos.id = tblempleados.idpuesto
		inner join tblbancos on  tblbancos.id = tblnominas.idbancos 
        inner join tbltipo_nominas  on tblnominas_pagoenc.idtiponomina = tbltipo_nominas.id
        where tblnominas_pagodet.id = ?;', [$idpagodet]);
        return collect($varempl);
    }

    public function obtenerRetencionesNominaExport($id)
    {
        $varempl = DB::select('
        SELECT
            tblempleados.id AS id_empleado,
            CASE
                WHEN tblempleados.segundo_nombre = " "
                THEN CONCAT(tblempleados.primer_nombre, " ", tblempleados.apellido_paterno, " ", tblempleados.apellido_materno)
                ELSE CONCAT(tblempleados.primer_nombre, " ", tblempleados.segundo_nombre, " ", tblempleados.apellido_paterno, " ", tblempleados.apellido_materno)
            END AS nombre_completo,
            tblsucursales.nombre AS departamento,
            tblpuestos.nombre AS puesto,
            tblempleados.rfc,
            tblempleados.nss,
            tblnominas_pagodet.total_sueldo,
            tblnominas_pagodet.total_horas_extras,
            tblnominas_pagodet.despensa,
            tblnominas_pagodet.otros,
            tblnominas_pagodet.percepcion_extraordinaria,
            tblnominas_pagodet.pago_dias_descanso,
            tblnominas_pagodet.pago_prima_dominical,
            tblnominas_pagodet.pago_dias_vacaciones,
            tblnominas_pagodet.pago_prima_vacacional,
            tblnominas_pagodet.pago_isr,
            tblnominas_pagodet.pago_imss,
            tblnominas_pagodet.pago_infonavit,
            tblnominas_pagodet.fonacot,
            tblnominas_pagodet.deudores_fiscal,
            tblnominas_pagodet.total_apagar
        FROM tblnominas_pagoenc
        INNER JOIN tblnominas_pagodet ON tblnominas_pagoenc.id = tblnominas_pagodet.idpagonomina
        INNER JOIN tblempleados ON tblempleados.id = tblnominas_pagodet.idempleado
        INNER JOIN tblnominas ON tblnominas.idempleado = tblempleados.id
        INNER JOIN tblsucursales ON tblsucursales.id = tblempleados.idsucursal
        INNER JOIN tblpuestos ON tblpuestos.id = tblempleados.idpuesto
        WHERE tblnominas_pagodet.idpagonomina = ?
        ORDER BY tblempleados.id ASC', [$id]);

        return collect($varempl);
    }

    public function obtenersucursalxempleado(int $id)
    {
        $sql = DB::select('select emp.idsucursal from tblempleados emp  inner join users u on emp.id = u.idempleado where u.idempleado = ?;', [$id]);
        return collect($sql);
    }




    public function obtenerrelacionespenxsuc(int $idcor)
    {
        $sql = DB::select('
    select enc.id_distribuidor,
    concat(dis.primer_nombre," ",dis.segundo_nombre," ",dis.apellido_paterno," ",dis.apellido_materno) as Didtribuidor,
    disval.id_coordinador
    from tblpagos_enc  enc
    inner join tbldistribuidores dis on enc.id_distribuidor = dis.id 
    inner join tbldistribuidor_valeras disval on dis.id = disval.iddistribuidor
    inner join tblempleados emp on emp.id = disval.id_coordinador
    where id_coordinador = ? and enc.estado_generado = "u" and enc.estado = "N";', [$idcor]);
        return collect($sql);
    }

    public function obtenehistorialxcoord(int $idcor)
    {
        $sql = DB::select('
    SELECT tblpagos_enc.fecha_relacion,tblpagos_enc.estado,sum(tblpagos_enc.saldo_pagar) as saldo,tbldistribuidor_valeras.id_coordinador 
    FROM tblpagos_enc 
    INNER JOIN tbldistribuidor_valeras on tblpagos_enc.id_distribuidor = tbldistribuidor_valeras.iddistribuidor 
    WHERE tbldistribuidor_valeras.id_coordinador = ? GROUP BY tblpagos_enc.fecha_relacion;', [$idcor]);
        return collect($sql);
    }

    public function oobtenerrelacioneshistorial(int $idcor, string $fecha)
    {
        $sql = DB::select('
    select tblpagos_enc.id_distribuidor,
    concat(tbldistribuidores.primer_nombre," ",tbldistribuidores.segundo_nombre," ",tbldistribuidores.apellido_paterno," ",tbldistribuidores.apellido_materno) as NombreDis,
    tbldistribuidor_valeras.id_coordinador 
    FROM tblpagos_enc 
    INNER JOIN tbldistribuidor_valeras on tblpagos_enc.id_distribuidor = tbldistribuidor_valeras.iddistribuidor 
    INNER JOIN tbldistribuidores on tbldistribuidor_valeras.iddistribuidor = tbldistribuidores.id 
    WHERE tbldistribuidor_valeras.id_coordinador = ? and tblpagos_enc.fecha_relacion = ?;', [$idcor, $fecha]);
        return collect($sql);
    }

    public function encabezadorelaciones(int $idids, string $fecharel)
    {
        $sql = DB::select('select
    enca.id as idpagoenc,
    cli.iddistribuidor,
    cli.id as numero_cliente,
    enc.folio_vale,
    enc.numero_plazos,
    cli.primer_nombre,
    cli.segundo_nombre, 
    cli.apellido_paterno,
    cli.apellido_materno,
    dis.primer_nombre as primer_nomDis, 
    dis.segundo_nombre as segundo_nomDis,
     dis.apellido_paterno as apellido_patDis,
     dis.apellido_materno as apellido_matDis,
     enc.id,
     det.fecha_pago,
     det.plazos as plazo_actual,
     det.saldo_nuevo,
     det.pago_total,
     det.status,
     det.id as id_detalle,
     enca.saldo_pagar
    from tblclientes_vales cli
    inner join tbldistribuidores dis on cli.iddistribuidor = dis.id
    inner join tblprestamos_valesenc enc on cli.id = enc.idcliente
    inner join tblprestamos_valesdet det on enc.id = det.idprestamo_vales
    inner join tblpagos_enc enca on enca.id_distribuidor = dis.id
    where cli.iddistribuidor = ? and det.fecha_pago = ? and enca.estado = "N" and det.status = "N";', [$idids, $fecharel]);
        return collect($sql);
    }

    public function encabezadorelacionesHistorial(int $idids, string $fecharel)
    {
        $sql = DB::select('select
    enca.id as idpagoenc,
    cli.iddistribuidor,
    cli.id as numero_cliente,
    cli.primer_nombre,
    cli.segundo_nombre, 
    cli.apellido_paterno,
    cli.apellido_materno,
    cli.status as statuscli,
    dis.primer_nombre as primer_nomDis, 
    dis.segundo_nombre as segundo_nomDis,
    dis.apellido_paterno as apellido_patDis,
    dis.apellido_materno as apellido_matDis,
    enc.id,
    enc.id as enc_id,
    enca.estado,
    enca.fecha_relacion,
    enca.fecha_corte_inicio,
    enca.fecha_corte_final,
    det.fecha_pago,
    enc.folio_vale,
    enc.numero_plazos,
    enc.fecha_canje,
    enc.monto_vale,
    enc.pago_totalredondeado,
    det.plazos as plazo_actual,
    (select count(tblprestamos_valesdet.id) as palzos_pagados  from tblprestamos_valesdet where tblprestamos_valesdet.idprestamo_vales = enc_id and tblprestamos_valesdet.status = "P") as plazos_pagados,
    (select sum(tblprestamos_valesdet.saldo) as atraso  from tblprestamos_valesdet where tblprestamos_valesdet.idprestamo_vales = enc_id) as atraso,
    det.saldo_nuevo,
    det.pago_total,
    det.status,
    enca.proteccion_saldo,
    det.id as id_detalle,
    enca.saldo_pagar,
   (select cantidad from tbldatos_iniciales where concepto = "COSTO_TRANSACCION" limit 1) as costo_transaccion
    from tblclientes_vales cli
    inner join tbldistribuidores dis on cli.iddistribuidor = dis.id
    inner join tblprestamos_valesenc enc on cli.id = enc.idcliente
    inner join tblprestamos_valesdet det on enc.id = det.idprestamo_vales
    inner join tblpagos_enc enca on enca.id_distribuidor = dis.id
    where cli.iddistribuidor = ?  and enc.status = "A" and enca.fecha_relacion  = ?  and det.fecha_pago = ?  GROUP BY cli.id;', [$idids, $fecharel, $fecharel]);
        return collect($sql);
    }


    public function encabezadorelacionesHistorial2(int $idids, string $fecharel)
    {
        $sql = DB::select('select
    enca.id as idpagoenc,
    cli.iddistribuidor,
    cli.id as numero_cliente,
    cli.primer_nombre,
    cli.segundo_nombre, 
    cli.apellido_paterno,
    cli.apellido_materno,
    cli.status as statuscli,
    dis.primer_nombre as primer_nomDis, 
    dis.segundo_nombre as segundo_nomDis,
    dis.apellido_paterno as apellido_patDis,
    dis.apellido_materno as apellido_matDis,
    enc.id,
    enc.id as enc_id,
    enca.estado,
    enca.fecha_relacion,
    enca.fecha_corte_inicio,
    enca.fecha_corte_final,
    det.fecha_pago,
    det.plazos as plazo_actual,
    enc.folio_vale,
    enc.numero_plazos,
    enc.fecha_canje,
    enc.monto_vale,
    enc.pago_totalredondeado,
    (select count(tblprestamos_valesdet.id) as palzos_pagados  from tblprestamos_valesdet where tblprestamos_valesdet.idprestamo_vales = enc_id and tblprestamos_valesdet.status = "P") as plazos_pagados,
    (select sum(tblprestamos_valesdet.saldo) as palzos_pagados  from tblprestamos_valesdet where tblprestamos_valesdet.idprestamo_vales = enc_id) as atraso,
    det.saldo_nuevo,
    det.pago_total,
    enca.proteccion_saldo,
    det.status,
    det.id as id_detalle,
    enca.saldo_pagar,
    enca.costo_transaccion as costo_transaccion
    from tblclientes_vales cli
    inner join tbldistribuidores dis on cli.iddistribuidor = dis.id
    inner join tblprestamos_valesenc enc on cli.id = enc.idcliente
    inner join tblprestamos_valesdet det on enc.id = det.idprestamo_vales
    inner join tblpagos_enc enca on enca.id_distribuidor = dis.id
    where cli.iddistribuidor = ?  and enc.status <> "CC" and enca.fecha_relacion  = ?  and det.fecha_pago = ?  GROUP BY cli.id;', [$idids, $fecharel, $fecharel]);
        return collect($sql);
    }


    public function obtnersaldosxrelacion(int $idids)
    {
        $sql = DB::select('select tbldistribuidores.id as iddis, tbldistribuidores.capital_autorizado as linea_credito, tbldistribuidores.capital as credito_disponible, sum(tblpagos_enc.monto_total) as abonado, (select sum(tblprestamos_valesenc.pago_totalredondeado) from tblprestamos_valesenc inner join tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente where tblclientes_vales.iddistribuidor = iddis and tblprestamos_valesenc.status <> "CC") as prestamos from tbldistribuidores inner join tblpagos_enc on tblpagos_enc.id_distribuidor = tbldistribuidores.id where tbldistribuidores.id = ?;', [$idids]);
        return collect($sql);
    }

    public function obtenerreferencias(int $idids)
    {
        $sql = DB::select("select referencia,id_fichapago from tblreferencias_pago where id_dis = ?", [$idids]);
        return collect($sql);
    }


    public function obtenerultimafecharel()
    {
        $sql = DB::select("SELECT tblpagos_enc.fecha_relacion FROM tblpagos_enc ORDER BY tblpagos_enc.fecha_relacion DESC LIMIT 1;");
        return collect($sql);
    }


    public function obtenersaldodistri(string $fecha_rel)
    {
        $sql = DB::select("SELECT dis.id_responsable as id_coordinador, tblempleados.primer_nombre, sum(enc.saldo_pagar) as Saldoquincenal, enc.fecha_relacion as fecha_pago 
    FROM tblpagos_enc enc 
    inner join tbldistribuidores dis on dis.id = enc.id_distribuidor 
    inner join tbldistribuidor_valeras disv on disv.iddistribuidor = dis.id 
    INNER JOIN tblempleados on tblempleados.id = dis.id_responsable 
    WHERE enc.fecha_relacion = ? AND enc.estado ='N' 
    group by dis.id_responsable 
    ORDER by tblempleados.primer_nombre ASC;", [$fecha_rel]);
        return collect($sql);
    }


    // public function obtenersaldodistri(){
//     $sql=DB::select("select 
//     disv.id_coordinador,
//     sum(enc.saldo_pagar)as Saldoquincenal, 
//     enc.fecha_relacion as fecha_pago
//     from tblpagos_enc enc
//     inner join tbldistribuidores dis on dis.id = enc.id_distribuidor
//     inner join tbldistribuidor_valeras disv on disv.iddistribuidor = dis.id
//     where enc.estado ='N'
//     group by disv.id_coordinador;");
//     return collect($sql);
// }


    // public function obtenersaldodistri(){
//     $sql=DB::select("select 
//     disv.id_coordinador,
//     sum(det.pago_total)as Saldoquincenal, 
//     det.fecha_pago
//     from tblprestamos_valesdet det
//     inner join tblprestamos_valesenc enc on det.idprestamo_vales = enc.id
//     inner join tblclientes_vales cli on cli.id = enc.idcliente
//     inner join tbldistribuidores dis on dis.id = cli.iddistribuidor
//     inner join tbldistribuidor_valeras disv on disv.iddistribuidor = dis.id
//     where det.status ='N'
//     group by disv.id_coordinador;");
//     return collect($sql);
// }



    public function descargaRel(string $fecha)
    {
        $sql = DB::select("select tbldistribuidor_valeras.id_coordinador, sum(tblpagos_enc.descargo) as descargo from tblpagos_enc 
    INNER JOIN tbldistribuidor_valeras on tbldistribuidor_valeras.iddistribuidor = tblpagos_enc.id_distribuidor 
    where tblpagos_enc.fecha_relacion  = ? 
    GROUP BY tbldistribuidor_valeras.id_coordinador;", [$fecha]);
        return collect($sql);
    }

    public function presdetdisFec(string $fecha, int $dis)
    {
        $sql = DB::select("select tblprestamos_valesdet.id, tblprestamos_valesdet.status
    from tblprestamos_valesdet 
    inner join tblprestamos_valesenc  on tblprestamos_valesdet.idprestamo_vales = tblprestamos_valesenc.id
    inner join tblclientes_vales  on tblclientes_vales.id = tblprestamos_valesenc.idcliente
    inner join tbldistribuidores  on tbldistribuidores.id = tblclientes_vales.iddistribuidor
    where tblprestamos_valesdet.fecha_pago  = ? AND tblclientes_vales.iddistribuidor  = ?;", [$fecha, $dis]);
        return collect($sql);
    }

    public function obtenerlistacordinadoresgeneral()
    {
        $sql = DB::select('select
    distinct(emp.id), 
    concat(emp.primer_nombre," ",emp.segundo_nombre," ",emp.apellido_paterno," ",emp.apellido_materno) as cordinador,
    pue.descripcion,
    suc.nombre
     from tblempleados emp
     inner join tblpuestos pue on emp.idpuesto = pue.id 
     inner join tblsucursales suc on suc.id = emp.idsucursal
     inner join tbldistribuidor_valeras disv on disv.id_coordinador = emp.id');
        return collect($sql);
    }



    public function obtenercoordinadoresxsucursal(int $idsucursal)
    {
        $sql = DB::select('select
    distinct(emp.id), 
    concat(emp.primer_nombre," ",emp.segundo_nombre," ",emp.apellido_paterno," ",emp.apellido_materno) as cordinador,
    pue.descripcion,
    suc.nombre
     from tblempleados emp
     inner join tblpuestos pue on emp.idpuesto = pue.id 
     inner join tblsucursales suc on suc.id = emp.idsucursal
     inner join tbldistribuidor_valeras disv on disv.id_coordinador = emp.id
    where emp.idsucursal = ?', [$idsucursal]);
        return collect($sql);
    }

    public function obtenercoordinadorxid(int $id)
    {
        $sql = DB::select('select
    distinct(emp.id), 
    concat(emp.primer_nombre," ",emp.segundo_nombre," ",emp.apellido_paterno," ",emp.apellido_materno) as cordinador,
    pue.descripcion,
    suc.nombre
     from tblempleados emp
     inner join tblpuestos pue on emp.idpuesto = pue.id 
     inner join tblsucursales suc on suc.id = emp.idsucursal
     inner join tbldistribuidor_valeras disv on disv.id_coordinador = emp.id 
     where emp.id = ?', [$id]);
        return collect($sql);
    }



    public function obtener_cancelaciones_solicitadas()
    {
        $varempl = DB::select('select 
    tblsolicitar_cancelacion_credemp.id_credito,
    tblsolicitar_cancelacion_credemp.estado,
    tblsolicitar_cancelacion_credemp.fecha,
    tblcreditos_empleado.tipo_credito,
    concat(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) as NombreEmp,
    tblsolicitar_cancelacion_credemp.descripcion,
    tblsolicitar_cancelacion_credemp.created_at,
    tblsolicitar_cancelacion_credemp.updated_at,
    tblsolicitar_cancelacion_credemp.created_by,
    tblsolicitar_cancelacion_credemp.updated_at
    from tblsolicitar_cancelacion_credemp
    inner join tblcreditos_empleado on tblcreditos_empleado.id = tblsolicitar_cancelacion_credemp.id_credito
    inner join tblempleados on tblempleados.id = tblcreditos_empleado.id_empleado
    where tblsolicitar_cancelacion_credemp.estado = "P" ORDER BY tblsolicitar_cancelacion_credemp.id desc;');
        return collect($varempl);
    }

    public function obtenerModulosCuentas()
    {
        $var = DB::select("select 
    tblmodulos_cuentas.id, 
    tblmodulos_cuentas.nombre as nombreModulo,
    tblvistas.nombre as nombreVista,
    tbldepartamentos.nombre as nombreDepartamento,
    tblmodulos_cuentas.descripcion,
    tblmodulos_cuentas.aplicacion,
    tblmodulos_cuentas.created_at,
    tblmodulos_cuentas.updated_at,
    tblmodulos_cuentas.created_by,
    tblmodulos_cuentas.updated_by  
    from tblmodulos_cuentas 
    inner join tblvistas on tblvistas.id = tblmodulos_cuentas.id_vista
    inner join tbldepartamentos on tbldepartamentos.id = tblvistas.iddepartamento;");
        return collect($var);
    }

    public function obtenerPermisosPorUsuario(int $id_user)
    {
        $var = DB::select('select id, id_modulo, tipo, id_tipo
            from tblpermisos_cuentas
            where id_user = ?;', [$id_user]);
        return collect($var);
    }

    public function validaPermisosModulo(int $id_user, int $id_modulo, string $tipo, int $id_tipo)
    {
        $varempl = DB::select('select * 
    from tblpermisos_cuentas 
    where tblpermisos_cuentas.id_user = ? and tblpermisos_cuentas.id_modulo = ? and tblpermisos_cuentas.tipo = ? and tblpermisos_cuentas.id_tipo = ?;', [$id_user, $id_modulo, $tipo, $id_tipo]);
        return collect($varempl);
    }

    public function obtenerModulosPermisosUser()
    {
        $var = DB::select("select 
    tblpermisos_cuentas.id, 
    tblpermisos_cuentas.id_modulo, 
    tblmodulos_cuentas.nombre as modulo, 
    tblpermisos_cuentas.id_user, 
    users.name as nameUser, 
    CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmpleado,
    tblpermisos_cuentas.tipo, 
    CASE 
    WHEN tblpermisos_cuentas.tipo = 'cuenta' then tblcuentas.nombre
    ELSE tblcajas.nombre
    END as tipo_nombre,
    tblpermisos_cuentas.id_tipo, 
    tblpermisos_cuentas.created_at,
    tblpermisos_cuentas.updated_at,
    tblpermisos_cuentas.created_by,
    tblpermisos_cuentas.updated_by  
    FROM tblpermisos_cuentas 
    INNER JOIN tblmodulos_cuentas on tblmodulos_cuentas.id = tblpermisos_cuentas.id_modulo
    LEFT JOIN tblcuentas ON tblpermisos_cuentas.id_tipo = tblcuentas.id
        AND tblpermisos_cuentas.tipo = 'cuenta'
    LEFT JOIN tblcajas ON tblpermisos_cuentas.id_tipo = tblcajas.id
        AND tblpermisos_cuentas.tipo = 'caja' OR tblpermisos_cuentas.tipo = 'caja_chica'
    INNER JOIN users on users.id = tblpermisos_cuentas.id_user
    INNER JOIN tblempleados on tblempleados.id = users.idempleado ORDER BY tblpermisos_cuentas.id asc;");
        return collect($var);
    }

    public function validaPermisoCuentas(int $id_user, string $modulo)
    {
        $varempl = DB::select('select * 
    from tblpermisos_cuentas 
    inner join tblmodulos_cuentas on tblpermisos_cuentas.id_modulo = tblmodulos_cuentas.id
    where tblpermisos_cuentas.id_user = ? and tblmodulos_cuentas.descripcion = ?;', [$id_user, $modulo]);
        return collect($varempl);
    }

    public function obtenerManejoCuentas(int $id_user, string $modulo)
    {
        $var = DB::select('select 
    tblpermisos_cuentas.id as id_permiso,
    tblpermisos_cuentas.tipo as tipo_nombre,
    tblcuentas.id,
    tblcuentas.nombre,
    tblcuentas.status,
    tblcuentas.tipo,
    tblcuentas.saldo_inicial,
    tblcuentas.saldo_actual,
    tblcuentas.fecha_alta,
    tblempresas.nombre_empresa as pertenencia,
    tblempresas.nombre_empresa as empresa,
    tblempresas.id as idempresa, 
    tblcuentas.created_at,
    tblcuentas.updated_at,
    tblcuentas.created_by,
    tblcuentas.updated_by,
    "" as id_tip,
    "" as arqueo
    FROM tblpermisos_cuentas 
    INNER JOIN tblmodulos_cuentas on tblpermisos_cuentas.id_modulo = tblmodulos_cuentas.id
    INNER JOIN tblcuentas on tblpermisos_cuentas.id_tipo = tblcuentas.id
    INNER JOIN tblempresas on tblempresas.id = tblcuentas.id_empresa 
    where tblpermisos_cuentas.id_user = ? and tblmodulos_cuentas.descripcion = ? and tblpermisos_cuentas.tipo = "cuenta";', [$id_user, $modulo]);
        return collect($var);
    }

    public function obtenerManejoCaja(int $id_user, string $modulo)
    {
        $var = DB::select('select 
    tblpermisos_cuentas.id as id_permiso,
    tblpermisos_cuentas.tipo  as tipo_nombre,
    tblcajas.id,
    tblcajas.nombre,
    tblcajas.status,
    tblcajas.tipo,
    tblcajas.saldo_inicial,
    tblcajas.saldo_actual,
    tblsucursales.nombre as pertenencia, 
    tblempresas.id as idempresa,
    tblempresas.nombre_empresa as empresa,
    tblcajas.fecha_alta,
    tblcajas.created_at,
    tblcajas.updated_at,
    tblcajas.created_by,
    tblcajas.updated_by,
    tblcajas.id as id_tip,
    (select tblarqueocajas.estado from tblarqueocajas where tblarqueocajas.id_caja = id_tip and tblarqueocajas.estado <> "Cancelado" order by id desc limit 1) as arqueo
    FROM tblpermisos_cuentas 
    INNER JOIN tblmodulos_cuentas on tblpermisos_cuentas.id_modulo = tblmodulos_cuentas.id
    INNER JOIN tblcajas on tblpermisos_cuentas.id_tipo = tblcajas.id
    INNER JOIN tblsucursales on tblsucursales.id = tblcajas.id_sucursal 
    INNER JOIN tblempresas on tblempresas.id = tblsucursales.idempresa
    where tblpermisos_cuentas.id_user = ? and tblmodulos_cuentas.descripcion = ? and tblcajas.tipo = "caja" and tblpermisos_cuentas.tipo <> "cuenta";', [$id_user, $modulo]);
        return collect($var);
    }

    public function obtenerManejoCajaChica(int $id_user, string $modulo)
    {
        $var = DB::select('select 
    tblpermisos_cuentas.id as id_permiso,
    tblpermisos_cuentas.tipo  as tipo_nombre,
    tblcajas.id,
    tblcajas.nombre,
    tblcajas.status,
    tblcajas.tipo,
    tblcajas.saldo_inicial,
    tblcajas.saldo_actual,
    tblsucursales.nombre as pertenencia, 
    tblempresas.id as idempresa,
    tblempresas.nombre_empresa as empresa,
    tblcajas.fecha_alta,
    tblcajas.created_at,
    tblcajas.updated_at,
    tblcajas.created_by,
    tblcajas.updated_by 
    FROM tblpermisos_cuentas 
    INNER JOIN tblmodulos_cuentas on tblpermisos_cuentas.id_modulo = tblmodulos_cuentas.id
    INNER JOIN tblcajas on tblpermisos_cuentas.id_tipo = tblcajas.id
    INNER JOIN tblsucursales on tblsucursales.id = tblcajas.id_sucursal 
    INNER JOIN tblempresas on tblempresas.id = tblsucursales.idempresa 
    where tblpermisos_cuentas.id_user = ? and tblmodulos_cuentas.descripcion = ? and tblcajas.tipo = "caja_chica" and tblpermisos_cuentas.tipo <> "cuenta";', [$id_user, $modulo]);
        return collect($var);
    }

    public function obtenerManejoCajas(int $id_user, string $modulo)
    {
        $var = DB::select('select 
        tblpermisos_cuentas.id as id_permiso,
        tblpermisos_cuentas.tipo  as tipo_nombre,
        tblcajas.id,
        tblcajas.nombre,
        tblcajas.status,
        tblcajas.tipo,
        tblcajas.saldo_inicial,
        tblcajas.saldo_actual,
        tblsucursales.nombre as pertenencia, 
        tblcajas.fecha_alta,
        tblcajas.created_at,
        tblcajas.updated_at,
        tblcajas.created_by,
        tblcajas.updated_by 
        FROM tblpermisos_cuentas 
        INNER JOIN tblmodulos_cuentas on tblpermisos_cuentas.id_modulo = tblmodulos_cuentas.id
        INNER JOIN tblcajas on tblpermisos_cuentas.id_tipo = tblcajas.id
        INNER JOIN tblsucursales on tblsucursales.id = tblcajas.id_sucursal 
        where tblpermisos_cuentas.id_user = ? and tblmodulos_cuentas.descripcion = ? and tblpermisos_cuentas.tipo <> "cuenta";', [$id_user, $modulo]);
        return collect($var);
    }


    public function validaResponsableCaja(int $id_user)
    {
        $varempl = DB::select('select 
        tblcajas.id,
        tblcajas.nombre,
        tblcajas.status,
        tblcajas.tipo,
        tblcajas.saldo_inicial,
        tblcajas.saldo_actual,
        tblempresas.id as idempresa,
        tblempresas.nombre_empresa as empresa,
        tblsucursales.nombre as pertenencia, 
        tblcajas.fecha_alta,
        tblcajas.created_at,
        tblcajas.updated_at,
        tblcajas.created_by,
        tblcajas.updated_by 
        FROM tblcajas
        INNER JOIN tblsucursales on tblsucursales.id = tblcajas.id_sucursal 
        INNER JOIN tblempresas on tblempresas.id = tblsucursales.idempresa 
        where tblcajas.id_usuario = ?;', [$id_user]);
        return collect($varempl);
    }

    public function obtenerResponsableCaja(int $id, string $fecha_inicio, string $fecha_fin)
    {
        $varhistcaj = DB::select("select 
        tblmovimientos_cajas.id,
        tblmovimientos_cajas.id_caja as id_tipo,
        tblcajas.nombre as cuenta,
        tblmovimientos_cajas.estado,
        tblmovimientos_cajas.id_empleado, 
        CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmp,
        tblsucursales.nombre as pertenencia, 
        tblempresas.id as idempresa,
        tblempresas.nombre_empresa as empresa,
        tblmovimientos_cajas.tipo_movimiento, 
        tblmovimientos_cajas.concepto,
        tblmovimientos_cajas.descripcion,
        tblmovimientos_cajas.responsable,
        tblmovimientos_cajas.total_iva ,
        tblmovimientos_cajas.total_ret_iva ,
        tblmovimientos_cajas.total_ret_isr ,
        tblmovimientos_cajas.total_ret_isr_resico ,
        tblmovimientos_cajas.ingreso, 
        tblmovimientos_cajas.egreso, 
        tblmovimientos_cajas.saldo, 
        tblmovimientos_cajas.ruta_evidencia, 
        tblmovimientos_cajas.numero_referencia,
        tblmovimientos_cajas.tipo_referencia, 
        tblmovimientos_cajas.numero_poliza, 
        tblmovimientos_cajas.created_at,
        tblmovimientos_cajas.updated_at, 
        tblmovimientos_cajas.created_by, 
        tblmovimientos_cajas.updated_by
        from tblmovimientos_cajas
        inner join tblcajas on tblcajas.id = tblmovimientos_cajas.id_caja 
        inner join tblempleados on tblempleados.id = tblmovimientos_cajas.id_empleado 
        inner join tblsucursales on tblsucursales.id = tblcajas.id_sucursal 
        inner join tblempresas on tblempresas.id = tblsucursales.idempresa 
        WHERE tblmovimientos_cajas.id_caja = ? and tblmovimientos_cajas.fecha >= ? and tblmovimientos_cajas.fecha <= ?
        order by tblmovimientos_cajas.id asc;", [$id, $fecha_inicio, $fecha_fin]);
        return collect($varhistcaj);
    }

    public function obtenerResponsableCajaDiario(int $id, string $fecha)
    {
        $varhistcaj = DB::select("select 
        tblmovimientos_cajas.id,
        tblmovimientos_cajas.fecha,
        tblmovimientos_cajas.id_caja as id_tipo,
        tblcajas.nombre as cuenta,
        tblmovimientos_cajas.estado,
        tblmovimientos_cajas.id_empleado, 
        CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmp,
        tblsucursales.nombre as pertenencia, 
        tblempresas.id as idempresa,
        tblempresas.nombre_empresa as empresa,
        tblmovimientos_cajas.tipo_movimiento, 
        tblmovimientos_cajas.concepto,
        tblmovimientos_cajas.descripcion,
        tblmovimientos_cajas.responsable,
        tblmovimientos_cajas.total_iva ,
        tblmovimientos_cajas.total_ret_iva ,
        tblmovimientos_cajas.total_ret_isr ,
        tblmovimientos_cajas.total_ret_isr_resico ,
        tblmovimientos_cajas.ingreso, 
        tblmovimientos_cajas.egreso, 
        tblmovimientos_cajas.saldo, 
        tblmovimientos_cajas.ruta_evidencia, 
        tblmovimientos_cajas.numero_referencia,
        tblmovimientos_cajas.tipo_referencia, 
        tblmovimientos_cajas.numero_poliza, 
        tblmovimientos_cajas.created_at,
        tblmovimientos_cajas.updated_at, 
        tblmovimientos_cajas.created_by, 
        tblmovimientos_cajas.updated_by
        from tblmovimientos_cajas
        inner join tblcajas on tblcajas.id = tblmovimientos_cajas.id_caja 
        inner join tblempleados on tblempleados.id = tblmovimientos_cajas.id_empleado 
        inner join tblsucursales on tblsucursales.id = tblcajas.id_sucursal 
        inner join tblempresas on tblempresas.id = tblsucursales.idempresa 
        WHERE tblmovimientos_cajas.id_caja = ? and tblmovimientos_cajas.fecha = ? 
        order by tblmovimientos_cajas.id asc;", [$id, $fecha]);
        return collect($varhistcaj);
    }


    public function obtenerPagosDiarios(int $id, string $fecha)
    {
        $varhistcaj = DB::select("select
            tblmovimientos_cajas.fecha,
            tblmovimientos_cajas.id,
            tblmovimientos_cajas.id_caja as id_tipo,
            tblcajas.nombre as cuenta,
            tblmovimientos_cajas.estado,
            tblmovimientos_cajas.id_empleado, 
            CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmp,
            tblsucursales.nombre as pertenencia, 
            tblempresas.id as idempresa,
            tblempresas.nombre_empresa as empresa,
            tblmovimientos_cajas.tipo_movimiento, 
            tblmovimientos_cajas.concepto,
            tblmovimientos_cajas.descripcion,
            tblmovimientos_cajas.responsable,
            tblmovimientos_cajas.ingreso, 
            tblmovimientos_cajas.saldo, 
            CONCAT(tbldistribuidores.primer_nombre,' ',tbldistribuidores.segundo_nombre,' ',tbldistribuidores.apellido_paterno,' ',tbldistribuidores.apellido_materno) AS nombre_referencia,
            tblmovimientos_cajas.numero_referencia,
            tblmovimientos_cajas.numero_poliza
            from tblmovimientos_cajas
            inner join tblcajas on tblcajas.id = tblmovimientos_cajas.id_caja 
            inner join tblempleados on tblempleados.id = tblmovimientos_cajas.id_empleado 
            inner join tblsucursales on tblsucursales.id = tblcajas.id_sucursal 
            inner join tblempresas on tblempresas.id = tblsucursales.idempresa 
            left join tblpagos_enc on tblmovimientos_cajas.numero_referencia = tblpagos_enc.id 
            left join tbldistribuidores on tblpagos_enc.id_distribuidor = tbldistribuidores.id 
            WHERE tblmovimientos_cajas.id_caja = ? and tblmovimientos_cajas.fecha = ? and tblmovimientos_cajas.tipo_movimiento = 'PAGO'
            order by tblmovimientos_cajas.id asc;", [$id, $fecha]);
        return collect($varhistcaj);
    }

    public function obtenerTraspasosDiarios(int $id, string $fecha)
    {
        $varhistcaj = DB::select("select
        tblmovimientos_cajas.fecha,
        tblmovimientos_cajas.id,
        tblmovimientos_cajas.id_caja as id_tipo,
        cj1.nombre as cuenta,
        tblmovimientos_cajas.tipo_movimiento, 
        tblmovimientos_cajas.estado,
        tblmovimientos_cajas.id_empleado, 
        CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmp,
        tblmovimientos_cajas.concepto,
        tblmovimientos_cajas.descripcion,
        tblmovimientos_cajas.responsable,
        tblmovimientos_cajas.ingreso, 
        tblmovimientos_cajas.saldo, 
        case when tblmovimientos_cajas.tipo_referencia = 'cuenta' then tblcuentas.nombre
        else tblcajas.nombre end
        as nombre_referencia,
        tblmovimientos_cajas.numero_referencia,
        tblmovimientos_cajas.numero_poliza
        from tblmovimientos_cajas
        inner join tblempleados on tblempleados.id = tblmovimientos_cajas.id_empleado
        inner join tblcajas cj1 on cj1.id = tblmovimientos_cajas.id_caja 
        LEFT JOIN tblcuentas ON tblmovimientos_cajas.numero_referencia = tblcuentas.id
            AND tblmovimientos_cajas.tipo_referencia = 'cuenta'
        LEFT JOIN tblcajas ON tblmovimientos_cajas.numero_referencia = tblcajas.id
            AND tblmovimientos_cajas.tipo_referencia = 'caja'
        WHERE tblmovimientos_cajas.id_caja = ? and tblmovimientos_cajas.fecha = ? and tblmovimientos_cajas.tipo_movimiento = 'INGRESO'
        order by tblmovimientos_cajas.id asc;", [$id, $fecha]);
        return collect($varhistcaj);
    }

    public function obtenerDesembolsosDiarios(int $id, string $fecha)
    {
        $varhistcaj = DB::select("select
        tblmovimientos_cajas.fecha,
        tblmovimientos_cajas.id,
        tblmovimientos_cajas.id_caja as id_tipo,
        tblcajas.nombre as cuenta,
        tblmovimientos_cajas.tipo_movimiento, 
        tblmovimientos_cajas.estado,
        tblmovimientos_cajas.id_empleado, 
        CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmp,
        tblmovimientos_cajas.concepto,
        tblmovimientos_cajas.descripcion,
        tblmovimientos_cajas.responsable,
        tblmovimientos_cajas.egreso, 
        tblmovimientos_cajas.saldo, 
        CONCAT(tblclientes_vales.primer_nombre,' ',tblclientes_vales.segundo_nombre,' ',tblclientes_vales.apellido_paterno,' ',tblclientes_vales.apellido_materno) AS nombre_referencia,
        tblmovimientos_cajas.numero_referencia,
        tblmovimientos_cajas.numero_poliza
        from tblmovimientos_cajas
        inner join tblempleados on tblempleados.id = tblmovimientos_cajas.id_empleado
        inner join tblcajas on tblcajas.id = tblmovimientos_cajas.id_caja 
        inner join tblprestamos_valesenc on tblprestamos_valesenc.id = tblmovimientos_cajas.numero_referencia 
        inner join tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente 
        WHERE tblmovimientos_cajas.id_caja = ? and tblmovimientos_cajas.fecha = ? and tblmovimientos_cajas.tipo_movimiento = 'DESEMBOLSO'
        order by tblmovimientos_cajas.id asc;", [$id, $fecha]);
        return collect($varhistcaj);
    }

    public function obtenerGastosDiarios(int $id, string $fecha)
    {
        $varhistcaj = DB::select("select
        tblmovimientos_cajas.fecha,
        tblmovimientos_cajas.id,
        tblmovimientos_cajas.id_caja as id_tipo,
        tblcajas.nombre as cuenta,
        tblmovimientos_cajas.tipo_movimiento, 
        tblmovimientos_cajas.estado,
        tblmovimientos_cajas.id_empleado, 
        CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmp,
        tblmovimientos_cajas.concepto,
        tblmovimientos_cajas.descripcion,
        tblmovimientos_cajas.responsable,
        tblmovimientos_cajas.egreso, 
        tblmovimientos_cajas.saldo, 
        tblmovimientos_cajas.numero_referencia,
        tblmovimientos_cajas.numero_poliza
        from tblmovimientos_cajas
        inner join tblempleados on tblempleados.id = tblmovimientos_cajas.id_empleado
        inner join tblcajas on tblcajas.id = tblmovimientos_cajas.id_caja 
        WHERE tblmovimientos_cajas.id_caja = ? and tblmovimientos_cajas.fecha = ? and tblmovimientos_cajas.tipo_movimiento = 'GASTO'
        order by tblmovimientos_cajas.id asc;", [$id, $fecha]);
        return collect($varhistcaj);
    }

    public function obtenerEntregasDiarias(int $id, string $fecha)
    {
        $varhistcaj = DB::select("select
            tblmovimientos_cajas.fecha,
            tblmovimientos_cajas.id,
            tblmovimientos_cajas.id_caja as id_tipo,
            cj1.nombre as cuenta,
            tblmovimientos_cajas.tipo_movimiento, 
            tblmovimientos_cajas.estado,
            tblmovimientos_cajas.id_empleado, 
            CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmp,
            tblmovimientos_cajas.concepto,
            tblmovimientos_cajas.descripcion,
            tblmovimientos_cajas.responsable,
            tblmovimientos_cajas.egreso, 
            tblmovimientos_cajas.saldo, 
            case when tblmovimientos_cajas.tipo_referencia = 'cuenta' then tblcuentas.nombre
            else tblcajas.nombre end
            as nombre_referencia,
            tblmovimientos_cajas.numero_referencia,
            tblmovimientos_cajas.numero_poliza
            from tblmovimientos_cajas
            inner join tblempleados on tblempleados.id = tblmovimientos_cajas.id_empleado
            inner join tblcajas cj1 on cj1.id = tblmovimientos_cajas.id_caja 
            LEFT JOIN tblcuentas ON tblmovimientos_cajas.numero_referencia = tblcuentas.id
                AND tblmovimientos_cajas.tipo_referencia = 'cuenta'
            LEFT JOIN tblcajas ON tblmovimientos_cajas.numero_referencia = tblcajas.id
                AND tblmovimientos_cajas.tipo_referencia = 'caja'
            WHERE tblmovimientos_cajas.id_caja = ? and tblmovimientos_cajas.fecha = ? and 
            tblmovimientos_cajas.tipo_movimiento = 'ENTREGA' or  
            tblmovimientos_cajas.tipo_movimiento = 'TRANSFERENCIA' and tblmovimientos_cajas.fecha = ?
            order by tblmovimientos_cajas.id asc;", [$id, $fecha, $fecha]);
        return collect($varhistcaj);
    }

    public function obtenerCancelacioneDiarias(int $id, string $fecha)
    {
        $varhistcaj = DB::select("select
            tblmovimientos_cajas.fecha,
            tblmovimientos_cajas.id,
            tblmovimientos_cajas.id_caja as id_tipo,
            tblcajas.nombre as cuenta,
            tblmovimientos_cajas.estado,
            tblmovimientos_cajas.id_empleado, 
            CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) AS nombreEmp,
            tblsucursales.nombre as pertenencia, 
            tblempresas.id as idempresa,
            tblempresas.nombre_empresa as empresa,
            tblmovimientos_cajas.tipo_movimiento, 
            tblmovimientos_cajas.concepto,
            tblmovimientos_cajas.descripcion,
            tblmovimientos_cajas.responsable,
            tblmovimientos_cajas.egreso, 
            tblmovimientos_cajas.saldo, 
            CONCAT(tbldistribuidores.primer_nombre,' ',tbldistribuidores.segundo_nombre,' ',tbldistribuidores.apellido_paterno,' ',tbldistribuidores.apellido_materno) AS nombre_referencia,
            tblmovimientos_cajas.numero_referencia,
            tblmovimientos_cajas.numero_poliza
            from tblmovimientos_cajas
            inner join tblcajas on tblcajas.id = tblmovimientos_cajas.id_caja 
            inner join tblempleados on tblempleados.id = tblmovimientos_cajas.id_empleado 
            inner join tblsucursales on tblsucursales.id = tblcajas.id_sucursal 
            inner join tblempresas on tblempresas.id = tblsucursales.idempresa 
            inner join tblpagos_enc on tblmovimientos_cajas.numero_referencia = tblpagos_enc.id 
            inner join tbldistribuidores on tblpagos_enc.id_distribuidor = tbldistribuidores.id 
            WHERE tblmovimientos_cajas.id_caja = ? and tblmovimientos_cajas.fecha = ? and tblmovimientos_cajas.tipo_movimiento = 'CANCELACION'
            order by tblmovimientos_cajas.id asc;", [$id, $fecha]);
        return collect($varhistcaj);
    }

    public function obtenerultimoarqueocaja()
    {
        $sql = "select id from tblarqueocajas order by id desc limit 1 ";
        $caja = DB::select($sql);
        return collect($caja);
    }

    public function checararqueo(string $fecha, int $id_caja)
    {
        $sql = DB::select("SELECT * FROM tblarqueocajas WHERE fecha = ? and estado <> 'Cancelado' and id_caja = ?;", [$fecha, $id_caja]);
        return collect($sql);
    }

    public function checararqueoaut()
    {
        $sql = DB::select("SELECT 
            CASE 
                WHEN tblarqueocajas.estado = 'Autorizado' or tblarqueocajas.estado = 'Cancelado' THEN 0
                ELSE tblarqueocajas.id 
            END as arqueo 
            FROM tblarqueocajas ORDER BY tblarqueocajas.id desc limit 1;");
        return collect($sql);
    }

    public function obtenerrelacionefect(int $id)
    {
        $var = DB::select("SELECT *
        FROM tblarqueo_relacion_efectivo
        WHERE tblarqueo_relacion_efectivo.id_arqueo = ?;", [$id]);
        return collect($var);
    }

    public function obtenerArqueosCajas(int $id, string $fecha_inicio = null, string $fecha_fin = null)
    {
        $query = "SELECT tblarqueocajas.id,
                    tblarqueocajas.id_caja,
                    tblcajas.nombre,
                    tblarqueocajas.fecha,
                    tblarqueocajas.estado,
                    tblarqueocajas.concepto,
                    tblarqueocajas.comentario,
                    tblarqueocajas.realizado,
                    CONCAT(emple.primer_nombre,' ',emple.segundo_nombre,' ',emple.apellido_paterno,' ',emple.apellido_materno) AS NombreRealizado,
                    tblarqueocajas.autoriza,
                    CONCAT(emp.primer_nombre,' ',emp.segundo_nombre,' ',emp.apellido_paterno,' ',emp.apellido_materno) AS NombreAutoriza,
                    tblarqueocajas.saldo_inicial,
                    tblarqueocajas.saldo_actual,
                    tblarqueocajas.total_traspasos,
                    tblarqueocajas.total_cobranza,
                    tblarqueocajas.total_ingresos,
                    tblarqueocajas.total_desembolsos,
                    tblarqueocajas.total_gastos,
                    tblarqueocajas.total_entregas,
                    tblarqueocajas.total_egresos,
                    tblarqueocajas.total_calculado,
                    tblarqueocajas.total_ingresado,
                    tblarqueocajas.diferencia,
                    tblarqueo_relacion_efectivo.diez,
                    tblarqueo_relacion_efectivo.cinco,
                    tblarqueo_relacion_efectivo.dos,
                    tblarqueo_relacion_efectivo.uno,
                    tblarqueo_relacion_efectivo.cincuentacentavos,
                    tblarqueo_relacion_efectivo.mil,
                    tblarqueo_relacion_efectivo.quinientos,
                    tblarqueo_relacion_efectivo.doscientos,
                    tblarqueo_relacion_efectivo.cien,
                    tblarqueo_relacion_efectivo.cincuenta,
                    tblarqueo_relacion_efectivo.veinte,
                    tblarqueocajas.created_at,
                    tblarqueocajas.created_by,
                    tblarqueocajas.updated_at,
                    tblarqueocajas.updated_by
                FROM tblarqueocajas
                INNER JOIN tblcajas on tblcajas.id = tblarqueocajas.id_caja
                INNER JOIN tblempleados emple on emple.id = tblarqueocajas.realizado 
                INNER JOIN tblarqueo_relacion_efectivo on tblarqueocajas.id = tblarqueo_relacion_efectivo.id_arqueo 
                LEFT JOIN tblempleados emp on emp.id = tblarqueocajas.autoriza 
                WHERE tblarqueocajas.id_caja = ? 
                AND tblarqueocajas.estado <> 'Cancelado'";

        if (!is_null($fecha_inicio) && !is_null($fecha_fin)) {
            $query .= " AND tblarqueocajas.fecha BETWEEN ? AND ?";
            $params = [$id, $fecha_inicio, $fecha_fin];
        } else {
            $params = [$id];
        }

        $query .= " ORDER BY tblarqueocajas.id DESC;";

        $var = DB::select($query, $params);
        return collect($var);
    }
    public function obtenerArqueoCajas(string $fecha, int $id)
    {
        $var = DB::select("SELECT tblarqueocajas.id,
                tblarqueocajas.id_caja,
                tblcajas.nombre,
                tblarqueocajas.fecha,
                tblarqueocajas.estado,
                tblarqueocajas.comentario,
                tblarqueocajas.realizado,
                CONCAT(emple.primer_nombre,' ',emple.segundo_nombre,' ',emple.apellido_paterno,' ',emple.apellido_materno) AS NombreRealizado,
                tblarqueocajas.autoriza,
                CONCAT(emp.primer_nombre,' ',emp.segundo_nombre,' ',emp.apellido_paterno,' ',emp.apellido_materno) AS NombreAutoriza,
                tblarqueocajas.saldo_inicial,
                tblarqueocajas.saldo_actual,
                tblarqueocajas.total_traspasos,
                tblarqueocajas.total_cobranza,
                tblarqueocajas.total_ingresos,
                tblarqueocajas.total_desembolsos,
                tblarqueocajas.total_gastos,
                tblarqueocajas.total_entregas,
                tblarqueocajas.total_cancelaciones,
                tblarqueocajas.total_egresos,
                tblarqueocajas.total_calculado,
                tblarqueocajas.total_ingresado,
                tblarqueocajas.diferencia,
                tblarqueo_relacion_efectivo.id as idarqueo_efect,
                tblarqueo_relacion_efectivo.diez,
                tblarqueo_relacion_efectivo.cinco,
                tblarqueo_relacion_efectivo.dos,
                tblarqueo_relacion_efectivo.uno,
                tblarqueo_relacion_efectivo.cincuentacentavos,
                tblarqueo_relacion_efectivo.mil,
                tblarqueo_relacion_efectivo.quinientos,
                tblarqueo_relacion_efectivo.doscientos,
                tblarqueo_relacion_efectivo.cien,
                tblarqueo_relacion_efectivo.cincuenta,
                tblarqueo_relacion_efectivo.veinte,
                tblarqueocajas.created_at,
                tblarqueocajas.created_by,
                tblarqueocajas.updated_at,
                tblarqueocajas.updated_by
            FROM tblarqueocajas
            INNER JOIN tblcajas on tblcajas.id = tblarqueocajas.id_caja
            INNER JOIN tblempleados emple on emple.id = tblarqueocajas.realizado 
            INNER JOIN tblarqueo_relacion_efectivo on tblarqueocajas.id = tblarqueo_relacion_efectivo.id_arqueo 
            left join tblempleados emp on emp.id = tblarqueocajas.autoriza 
            WHERE tblarqueocajas.fecha = ? and tblarqueocajas.id_caja = ? and tblarqueocajas.estado <> 'Cancelado'
            ORDER BY tblarqueocajas.id asc;", [$fecha, $id]);
        return collect($var);
    }

    public function obtenerdepositosquincena(int $id)
    {
        $var = DB::select('SELECT "01720161060432",
                "SERVICIOS ADMINISTRATIVOS CREDILAGUNA",
                "SAC2103169C5",
                "PAGO DE NOMINA",
                b.total_nomina_fiscal,
                ba.moneda,
                ba.clave,
                ba.tipo_cuenta,
                a.numero_cuenta,
                concat(e.primer_nombre," ",e.segundo_nombre," ",e.apellido_paterno," ",e.apellido_materno) as titular,
                e.tipo_tranferencia,
                "PAGO DE NOMINA" 
            FROM tblnominas a 
            INNER JOIN tblnominas_pagodet b on a.idempleado = b.idempleado 
            INNER JOIN tblnominas_pagoenc c on c.id = b.idpagonomina
            INNER JOIN tblempleados e on e.id = a.idempleado 
            INNER JOIN tblbancos ba on ba.id = e.idbanco WHERE b.total_nomina_fiscal > 0 and b.idpagonomina = ?;', [$id]);
        return collect($var);
    }

    public function validarArqueoRealizado(int $id){
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');

        $varhistcuent = DB::select("select tblarqueocajas.estado from tblarqueocajas 
        where tblarqueocajas.id_caja = ?  and tblarqueocajas.estado <> 'Cancelado' and fecha <> ? order by id desc limit 1;",[$id,$fecha]);
        return collect($varhistcuent);
    }

    public function datos_nomina(?string $tipoEmpresa = 'GENERAL', ?int $idEmpresa = null){
        $sql = "
        select nom.idempleado , nom.* ,emp.fecha_ingreso from tblempleados emp
        inner join tblnominas nom on emp.id = nom.idempleado 
        WHERE emp.estado = 'A'";

        $params = [];

        if ($tipoEmpresa === 'EMPRESA' && $idEmpresa) {
            $sql .= " AND nom.idempresa = ?";
            $params[] = $idEmpresa;
        }

        $sql .= ";";

        $var = DB::select($sql, $params);
        return collect($var);
    }

    public function datos_nomina_insertada(int $idnom){
        $var = DB::select("
        select
        tblempleados.id as idempleado,
        tblnominas_pagoenc.fecha_inicio,
 		tblnominas_pagoenc.fecha_fin,
 		tblnominas.*,
        tblnominas_pagodet.id as idpago_detalle,
        tblnominas_pagodet.sueldo_excedente,
        tblnominas_pagodet.percepcion_extraordinaria,
        tblnominas_pagodet.pago_prima_vacacional,
        tblnominas_pagodet.otros,
        tblnominas_pagodet.bono,
        tblnominas_pagodet.viaticos,
        tblnominas_pagodet.despensa,
        tblnominas_pagodet.horas_extras,
        tblnominas_pagodet.deudores_fiscal,
        tblnominas_pagodet.ahorro,
        tblnominas_pagodet.fonacot,
        tblnominas_pagodet.dias_incapacidad,
        tblnominas_pagodet.faltas_reta_aus,
        tblnominas_pagodet.dias_descanso,
        tblnominas_pagodet.dias_prima_dominical,
        tblnominas_pagodet.pago_prima_dominical,
        tblnominas_pagodet.pago_dias_descanso,
        tblnominas_pagodet.dias_vaciones,
        tblnominas_pagodet.dias_prima_vacacional,
        tblnominas_pagodet.pago_prima_vacacional_fis,
        tblnominas_pagodet.pago_prima_vacacional_exce,
        tblempleados.fecha_ingreso
        from tblnominas_pagoenc 
        inner join tblnominas_pagodet on tblnominas_pagoenc.id = tblnominas_pagodet.idpagonomina
        inner join tblempleados on  tblempleados.id = tblnominas_pagodet.idempleado
        inner join tblnominas on tblnominas.idempleado = tblempleados.id 
        WHERE  tblempleados.estado = 'A' and tblnominas_pagoenc.id = ?;",[$idnom]);
        return collect($var);
    }

    public function seleciona_tarifa_isr(float $salario_mensual, int $id_tipo_nomina = 2){
        $var = DB::select(
            "select * FROM tbltarifas_isr where id_tipo_nomina = ? and ? between limite_inferior and limite_superior;",
            [$id_tipo_nomina, $salario_mensual]
        );
        return collect($var);
    }

    public function obtener_isrlista(int $id_tipo_nomina = null){
        if ($id_tipo_nomina) {
            $var = DB::select(
                "select * FROM tbltarifas_isr where id_tipo_nomina = ? order by limite_inferior;",
                [$id_tipo_nomina]
            );
        } else {
            $var = DB::select("select * FROM tbltarifas_isr order by id_tipo_nomina, limite_inferior");
        }
        return collect($var);
    }

    public function seleciona_subsidio(){
        $var = DB::select("select * FROM tbltarifas_subsidio;");
        return collect($var);
    }

    public function seleciona_subsidio_por_tipo(string $tipo){
        $var = DB::select("select * FROM tbltarifas_subsidio where tipo = ?;", [$tipo]);
        return collect($var);
    }

    public function obtener_tarifas_integracion(){
        $var = DB::select("select * FROM tbltarifas_integracion");
        return collect($var);
    }

    public function selecciona_tarifas_integracion(int $año){
        $var = DB::select("select * FROM tbltarifas_integracion where ? between min_años and max_años;",[$año]);
        return collect($var);
    }

    public function ObtenerIdEmisor()
    {
        $idemisor = DB::select("SELECT uuid_emisor FROM `tblfacturify`");
        return  collect($idemisor);
    }

    public function ObtenerDatosFacturify()
    {
        $idemisor = DB::select("SELECT * FROM `tblfacturify`");
        return  collect($idemisor);
    }

    public function ultima_nomina_insertada(){
        $var = DB::select("select id FROM tblnominas_pagoenc ORDER by id DESC LIMIT 1;");

        foreach($var as $key){
           $id = $key->id;
        }
        return $id;
    }


    public function TraerIncapacidades(){
        $var = DB::select("select 
            CONCAT(emp.primer_nombre,' ',emp.segundo_nombre,' ',emp.apellido_paterno,' ',emp.apellido_materno) AS NombreEmpleado,
            CONCAT(aut.primer_nombre,' ',aut.segundo_nombre,' ',aut.apellido_paterno,' ',aut.apellido_materno) AS NombreAutorizo,
            tblincapacidades.* 
            from tblincapacidades
            inner join tblempleados emp on tblincapacidades.id_empleado = emp.id
            left join tblempleados aut on tblincapacidades.id_autorizo = aut.id
            order by tblincapacidades.id desc;");
        return $var;

    }

    public function TraerVacaciones(){
        $var = DB::select("select 
            CONCAT(emp.primer_nombre,' ',emp.segundo_nombre,' ',emp.apellido_paterno,' ',emp.apellido_materno) AS NombreEmpleado,
            CONCAT(aut.primer_nombre,' ',aut.segundo_nombre,' ',aut.apellido_paterno,' ',aut.apellido_materno) AS NombreAutorizo,
            tblvacaciones.* 
            from tblvacaciones
            inner join tblempleados emp on tblvacaciones.id_empleado = emp.id
            left join tblempleados aut on tblvacaciones.id_autorizo = aut.id
            order by tblvacaciones.id desc;");
        return $var;

    }

public function Listadoempleadosnotimbrados(int $id)
{
    $lista = DB::select("select * from tblnomina_notimbrados where idnominapago_enc = ?;",[$id]);
    return collect($lista);
}

public function optenerproveedoresuser()
{
    $lista = DB::select("select * FROM tblprovedores where id_usuario is NULL;");
    return collect($lista);
}

public function ulmtimouser()
{
    $iduser = 0;
    $lista = DB::select("select * FROM users order by id DESC limit 1;");
    $lista = collect($lista);

    foreach($lista as $item){
        $iduser = $item->id;
    }

    return $iduser;
}


public function obtenerClientes()
{
    $lista = DB::select("select tblclientes.*, tblciudades.nombre as nombre_ciudad , tblestados.nombre as nombre_estado
    FROM tblclientes
    INNER JOIN tblciudades on tblciudades.id =  tblclientes.id_ciudad
	INNER JOIN tblestados on tblestados.id =  tblciudades.idestado;");
    return collect($lista);
}

public function obtenerClientesActivos()
{
    $lista = DB::select("select tblclientes.*, tblciudades.nombre as nombre_ciudad , tblestados.nombre as nombre_estado
    FROM tblclientes
    INNER JOIN tblciudades on tblciudades.id =  tblclientes.id_ciudad
	INNER JOIN tblestados on tblestados.id =  tblciudades.idestado where tblclientes.estado = 'A';");
    return collect($lista);
}


public function obtnerultimocliente()
{
    $lista = DB::select("select * FROM tblclientes where tblclientes.estado = 'A' order by id DESC limit 1;");
    $lista = collect($lista);

    foreach($lista as $key){
        $id = $key->id;
    }

    return $id;
}

    public function total_ingresos()
    {
        $sql = DB::select("select SUM(ingreso) AS total_ingresos
            FROM (
                SELECT ingreso, fecha
                FROM tblmovimientos_cuentas 
                WHERE ingreso > 0
                AND fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()

                UNION ALL

                SELECT ingreso, fecha
                FROM tblmovimientos_cajas 
                WHERE ingreso > 0
                AND fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()
            ) AS ingresos_unidos;");

            $totalIngresos = $sql[0]->total_ingresos ?? 0;
        return $totalIngresos;
    }

    public function total_egresos()
    {
        $sql = DB::select("select SUM(egreso) AS total_egresos
        FROM (
            SELECT egreso, fecha
            FROM tblmovimientos_cuentas 
            WHERE egreso > 0
            AND fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()

            UNION ALL

            SELECT egreso, fecha
            FROM tblmovimientos_cajas 
            WHERE egreso > 0
            AND fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()
        ) AS egreso_unidos;");
        $totalEgresos = $sql[0]->total_egresos ?? 0;
        return $totalEgresos;
    }


}
