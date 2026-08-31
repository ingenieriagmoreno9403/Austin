<?php

namespace App\Traits;
use DB;
use Log;

trait NotificacionesTrait
{
    public function obtenerNotificaciones($id)
    {
        return DB::table('tblnotificaciones')
            ->where('id_usuario', $id)
            ->where('estado', 0)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function obtenerNotificacionesTodas($id)
    {
        return DB::table('tblnotificaciones')
            ->where('id_usuario', $id)
            ->orderBy('id', 'desc')
            ->get();
    }

    
    public function obtenerNotificacionesporFecha(string $fecha_hoy, string $fecha_atras)
    {
        try {
            return DB::table('tblnotificaciones')
                ->join('tblempleados', 'tblnotificaciones.id_empleado', '=', 'tblempleados.id')
                ->join('tblcajas', 'tblnotificaciones.id_caja', '=', 'tblcajas.id')
                ->select(DB::raw("CONCAT(tblempleados.primer_nombre, ' ', tblempleados.segundo_nombre, ' ', tblempleados.apellido_paterno, ' ', tblempleados.apellido_materno) as nombre"), 
                         'tblnotificaciones.*', 'tblcajas.nombre as nombre_caja')
                ->whereBetween('tblnotificaciones.fecha_alta', [$fecha_atras . ' 00:00:00', $fecha_hoy . ' 23:59:59'])
                ->where('tblnotificaciones.estado', 'P')
                ->orderBy('tblnotificaciones.id', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al obtener notificaciones: ' . $e->getMessage());
            return collect(); // Retorna una colección vacía en caso de error
        }
    }
}