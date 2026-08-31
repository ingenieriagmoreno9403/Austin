<?php

namespace App\Mail;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrdenCompraEnviada extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * La orden de compra.
     *
     * @var \App\Models\OrdenCompra
     */
    public $ordenCompra;

    /**
     * Create a new message instance.
     *
     * @param \App\Models\OrdenCompra $ordenCompra
     * @return void
     */
    public function __construct(OrdenCompra $ordenCompra)
    {
        $this->ordenCompra = $ordenCompra;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Nueva Orden de Compra #' . $this->ordenCompra->id)
                    ->markdown('emails.ordenes.enviada')
                    ->with([
                        'ordenCompra' => $this->ordenCompra,
                        'proveedor' => $this->ordenCompra->proveedor,
                        'detalles' => $this->ordenCompra->detalles,
                    ]);
    }
} 