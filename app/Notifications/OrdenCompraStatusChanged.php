<?php

namespace App\Notifications;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrdenCompraStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ordenCompra;
    protected $estadoAnterior;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(OrdenCompra $ordenCompra, $estadoAnterior = null)
    {
        $this->ordenCompra = $ordenCompra;
        $this->estadoAnterior = $estadoAnterior;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mensaje = (new MailMessage)
            ->subject('Actualización de Orden de Compra #' . $this->ordenCompra->id)
            ->greeting('Hola ' . $notifiable->nombre . ',')
            ->line('La orden de compra #' . $this->ordenCompra->id . ' ha cambiado su estado.');
            
        if ($this->estadoAnterior) {
            $mensaje->line('Estado anterior: ' . OrdenCompra::$estados[$this->estadoAnterior] ?? $this->estadoAnterior);
        }
        
        $mensaje->line('Nuevo estado: ' . $this->ordenCompra->estado_legible)
            ->line('Proveedor: ' . $this->ordenCompra->proveedor->nombre)
            ->line('Fecha límite: ' . $this->ordenCompra->fecha_limite);
            
        if ($this->ordenCompra->estado == OrdenCompra::ESTADO_ENVIADA) {
            $mensaje->action('Ver Orden de Compra', url('/OrdenCompras/show/' . $this->ordenCompra->id));
        }
            
        $mensaje->line('¡Gracias por utilizar nuestra aplicación!');
            
        return $mensaje;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'orden_compra_id' => $this->ordenCompra->id,
            'estado_anterior' => $this->estadoAnterior,
            'estado_nuevo' => $this->ordenCompra->estado,
            'titulo' => 'Cambio de estado en Orden de Compra #' . $this->ordenCompra->id,
            'mensaje' => 'La orden de compra ha cambiado de ' . 
                        (OrdenCompra::$estados[$this->estadoAnterior] ?? 'estado anterior') . 
                        ' a ' . $this->ordenCompra->estado_legible,
            'fecha' => now()->toDateTimeString(),
        ];
    }
} 