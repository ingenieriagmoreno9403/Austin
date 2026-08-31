<?php

namespace App\Http\Controllers;

use App\Traits\MenuTrait;
use Illuminate\Contracts\View\View;

class MesaControlController extends Controller
{
    use MenuTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $pedidos = $this->getPedidosFicticios();

        $empleadosSurtidor = [
            'Carlos Ramirez',
            'Pedro Morales',
            'Ana Martinez',
            'Luis Hernandez',
        ];

        return view('Gestion_wms.mesa_control', compact('varpantallas', 'varsubmenus', 'pedidos', 'empleadosSurtidor'));
    }

    public function empaque(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $pedidos = $this->getPedidosFicticios();

        return view('Gestion_wms.empaque', compact('varpantallas', 'varsubmenus', 'pedidos'));
    }

    public function embarques(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $embarques = $this->getEmbarquesFicticios();

        return view('Gestion_wms.embarques', compact('varpantallas', 'varsubmenus', 'embarques'));
    }

    public function devoluciones(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $devoluciones = $this->getDevolucionesFicticias();

        return view('Gestion_wms.devoluciones', compact('varpantallas', 'varsubmenus', 'devoluciones'));
    }

    private function getPedidosFicticios(): array
    {
        return [
            [
                'id' => 1,
                'numero_pedido' => 'PED-0001',
                'numero_cliente' => 'CLI-104',
                'nombre_cliente' => 'Comercializadora del Norte',
                'estatus_pedido' => 'Pendiente',
                'prioridad' => 'Normal',
                'ciudad' => 'Monterrey',
                'telefono_cliente' => '8181234567',
                'surtidor_asignado' => null,
                'progreso_surtido' => null,
                'producto_actual' => null,
                'detalle_productos' => [
                    ['nombre' => 'Guante nitrilo azul', 'cantidad' => 20],
                    ['nombre' => 'Cinta de empaque 48 mm', 'cantidad' => 15],
                    ['nombre' => 'Caja carton 30x30', 'cantidad' => 12],
                ],
            ],
            [
                'id' => 2,
                'numero_pedido' => 'PED-0002',
                'numero_cliente' => 'CLI-225',
                'nombre_cliente' => 'Distribuciones Rojas',
                'estatus_pedido' => 'En surtido',
                'prioridad' => 'Alta',
                'ciudad' => 'Saltillo',
                'telefono_cliente' => '8444567890',
                'surtidor_asignado' => 'Pedro Morales',
                'progreso_surtido' => [
                    'actual' => 4,
                    'total' => 7,
                ],
                'producto_actual' => 'Guantes de seguridad',
                'detalle_productos' => [
                    ['nombre' => 'Guantes de seguridad', 'cantidad' => 30],
                    ['nombre' => 'Botas dieléctricas', 'cantidad' => 8],
                    ['nombre' => 'Lentes de seguridad', 'cantidad' => 14],
                    ['nombre' => 'Casco industrial', 'cantidad' => 10],
                    ['nombre' => 'Chaleco reflejante', 'cantidad' => 22],
                    ['nombre' => 'Tapones auditivos', 'cantidad' => 60],
                    ['nombre' => 'Faja lumbar', 'cantidad' => 9],
                ],
            ],
            [
                'id' => 3,
                'numero_pedido' => 'PED-0003',
                'numero_cliente' => 'CLI-330',
                'nombre_cliente' => 'Almacenes Rivera',
                'estatus_pedido' => 'Listo para embarque',
                'prioridad' => 'Baja',
                'ciudad' => 'Torreon',
                'telefono_cliente' => '8712345678',
                'surtidor_asignado' => 'Ana Martinez',
                'progreso_surtido' => [
                    'actual' => 7,
                    'total' => 7,
                ],
                'producto_actual' => 'Caja de empaque 40x40',
                'detalle_productos' => [
                    ['nombre' => 'Caja de empaque 40x40', 'cantidad' => 50],
                    ['nombre' => 'Rollo de burbuja', 'cantidad' => 12],
                    ['nombre' => 'Etiqueta adhesiva', 'cantidad' => 120],
                    ['nombre' => 'Film stretch', 'cantidad' => 9],
                ],
            ],
            [
                'id' => 4,
                'numero_pedido' => 'PED-0004',
                'numero_cliente' => 'CLI-412',
                'nombre_cliente' => 'Super Ferretera Laguna',
                'estatus_pedido' => 'Pendiente',
                'prioridad' => 'Normal',
                'ciudad' => 'Gomez Palacio',
                'telefono_cliente' => '8719981122',
                'surtidor_asignado' => null,
                'progreso_surtido' => null,
                'producto_actual' => null,
                'detalle_productos' => [
                    ['nombre' => 'Tornillo pija 1 pulgada', 'cantidad' => 350],
                    ['nombre' => 'Taquete plastico 3/16', 'cantidad' => 350],
                    ['nombre' => 'Broca para concreto 1/4', 'cantidad' => 40],
                ],
            ],
            [
                'id' => 5,
                'numero_pedido' => 'PED-0005',
                'numero_cliente' => 'CLI-518',
                'nombre_cliente' => 'Refacciones El Motor',
                'estatus_pedido' => 'En surtido',
                'prioridad' => 'Alta',
                'ciudad' => 'Monclova',
                'telefono_cliente' => '8663419087',
                'surtidor_asignado' => 'Carlos Ramirez',
                'progreso_surtido' => [
                    'actual' => 2,
                    'total' => 5,
                ],
                'producto_actual' => 'Aceite multigrado 5W-30',
                'detalle_productos' => [
                    ['nombre' => 'Filtro de aceite', 'cantidad' => 25],
                    ['nombre' => 'Aceite multigrado 5W-30', 'cantidad' => 40],
                    ['nombre' => 'Bujia iridium', 'cantidad' => 60],
                    ['nombre' => 'Liquido de frenos DOT4', 'cantidad' => 20],
                    ['nombre' => 'Anticongelante', 'cantidad' => 18],
                ],
            ],
            [
                'id' => 6,
                'numero_pedido' => 'PED-0006',
                'numero_cliente' => 'CLI-601',
                'nombre_cliente' => 'Comercial Mayoreo Garza',
                'estatus_pedido' => 'Pendiente',
                'prioridad' => 'Baja',
                'ciudad' => 'Durango',
                'telefono_cliente' => '6181457788',
                'surtidor_asignado' => null,
                'progreso_surtido' => null,
                'producto_actual' => null,
                'detalle_productos' => [
                    ['nombre' => 'Papel higienico industrial', 'cantidad' => 80],
                    ['nombre' => 'Jabon liquido 5L', 'cantidad' => 24],
                    ['nombre' => 'Toalla interdoblada', 'cantidad' => 60],
                    ['nombre' => 'Cloro 1L', 'cantidad' => 48],
                ],
            ],
        ];
    }

    private function getEmbarquesFicticios(): array
    {
        return [
            [
                'id' => 101,
                'numero_pedido' => 'PED-0002',
                'numero_cliente' => 'CLI-225',
                'nombre_cliente' => 'Distribuciones Rojas',
                'ubicacion_recoleccion' => 'Anden A-03',
                'estatus_entrega' => 'En proceso de embarque',
                'chofer' => 'Jorge Salinas',
                'unidad' => 'Nissan NP300',
                'placas' => 'ER-52-901',
                'ruta' => 'Saltillo Centro',
                'fecha_salida' => '2026-05-11 09:30',
                'fecha_entrega_estimada' => '2026-05-11 13:00',
                'tracking' => 'TRK-0002-WMS',
                'observaciones' => 'Validar sello de caja antes de salida.',
            ],
            [
                'id' => 102,
                'numero_pedido' => 'PED-0003',
                'numero_cliente' => 'CLI-330',
                'nombre_cliente' => 'Almacenes Rivera',
                'ubicacion_recoleccion' => 'Anden B-01',
                'estatus_entrega' => 'En camino al cliente',
                'chofer' => 'Martha Ortega',
                'unidad' => 'Hino 300',
                'placas' => 'FK-18-642',
                'ruta' => 'Torreon Norte',
                'fecha_salida' => '2026-05-11 08:10',
                'fecha_entrega_estimada' => '2026-05-11 11:40',
                'tracking' => 'TRK-0003-WMS',
                'observaciones' => 'Cliente solicita llamada 30 min antes.',
            ],
            [
                'id' => 103,
                'numero_pedido' => 'PED-0005',
                'numero_cliente' => 'CLI-518',
                'nombre_cliente' => 'Refacciones El Motor',
                'ubicacion_recoleccion' => 'Anden C-02',
                'estatus_entrega' => 'Entregado',
                'chofer' => 'Luis Cedillo',
                'unidad' => 'Sprinter 415',
                'placas' => 'MX-44-713',
                'ruta' => 'Monclova Industrial',
                'fecha_salida' => '2026-05-10 15:20',
                'fecha_entrega_estimada' => '2026-05-10 18:00',
                'tracking' => 'TRK-0005-WMS',
                'observaciones' => 'Entrega firmada por recepcion.',
            ],
            [
                'id' => 104,
                'numero_pedido' => 'PED-0006',
                'numero_cliente' => 'CLI-601',
                'nombre_cliente' => 'Comercial Mayoreo Garza',
                'ubicacion_recoleccion' => 'Anden A-01',
                'estatus_entrega' => 'En proceso de embarque',
                'chofer' => 'Rafael Quiroga',
                'unidad' => 'Isuzu ELF',
                'placas' => 'DL-30-502',
                'ruta' => 'Durango Centro',
                'fecha_salida' => '2026-05-11 10:15',
                'fecha_entrega_estimada' => '2026-05-11 16:00',
                'tracking' => 'TRK-0006-WMS',
                'observaciones' => 'Mercancia fragil en 2 tarimas.',
            ],
        ];
    }

    private function getDevolucionesFicticias(): array
    {
        return [
            [
                'id' => 201,
                'numero_pedido' => 'PED-0002',
                'numero_cliente' => 'CLI-225',
                'nombre_cliente' => 'Distribuciones Rojas',
                'tipo_devolucion' => 'Parcial',
                'motivo' => 'Producto danado en traslado',
                'estatus_devolucion' => 'Solicitud recibida',
                'folio_factura' => 'FAC-2026-0021',
                'cancelacion_factura' => 'Pendiente',
                'monto_referencia' => '$3,240.00',
                'productos_devueltos' => [
                    ['nombre' => 'Guantes de seguridad', 'cantidad' => 4],
                    ['nombre' => 'Lentes de seguridad', 'cantidad' => 2],
                ],
            ],
            [
                'id' => 202,
                'numero_pedido' => 'PED-0003',
                'numero_cliente' => 'CLI-330',
                'nombre_cliente' => 'Almacenes Rivera',
                'tipo_devolucion' => 'Completa',
                'motivo' => 'Pedido incorrecto',
                'estatus_devolucion' => 'En revision',
                'folio_factura' => 'FAC-2026-0027',
                'cancelacion_factura' => 'En proceso',
                'monto_referencia' => '$9,870.00',
                'productos_devueltos' => [
                    ['nombre' => 'Caja de empaque 40x40', 'cantidad' => 50],
                    ['nombre' => 'Rollo de burbuja', 'cantidad' => 12],
                    ['nombre' => 'Etiqueta adhesiva', 'cantidad' => 120],
                ],
            ],
            [
                'id' => 203,
                'numero_pedido' => 'PED-0005',
                'numero_cliente' => 'CLI-518',
                'nombre_cliente' => 'Refacciones El Motor',
                'tipo_devolucion' => 'Parcial',
                'motivo' => 'Inconformidad por lote',
                'estatus_devolucion' => 'Autorizada',
                'folio_factura' => 'FAC-2026-0034',
                'cancelacion_factura' => 'No requerida',
                'monto_referencia' => '$1,120.00',
                'productos_devueltos' => [
                    ['nombre' => 'Filtro de aceite', 'cantidad' => 5],
                ],
            ],
        ];
    }
}
