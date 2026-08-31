<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Licitacion;

class LicitacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $licitacion;

    /**
     * Crear una nueva instancia de mensaje.
     *
     * @param int $licitacionId
     * @return void
     */
    public function __construct($licitacionId)
    {
        $this->licitacion = Licitacion::findOrFail($licitacionId);
    }

    /**
     * Construir el mensaje.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Información sobre Licitación')
                    ->view('emails.licitacion') // Vista de correo
                    ->with([
                        'licitacion' => $this->licitacion,
                    ]);
    }
}
