<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ServiciosEnc extends Model
{
    use HasFactory;
    public $table='tblservicios_enc';
    protected $fillable=['id', 'folio', 'nombre', 'id_tiposervicio', 'estado', 'id_vendedor', 'id_cliente', 'id_atencion', 'fecha_inicio', 'fecha_limite', 'fecha_tentativa_pago', 'fecha_entrega_realizada', 'nivel_progreso', 'solicita_licitacion', 'otrosconceptos1', 'otrosconceptos2', 'otrosconceptos3', 'created_at', 'updated_at', 'created_by', 'updated_by'];
    
    /**
     * Reglas de validación para el modelo
     */
    public static function rules($id = null)
    {
        return [
            'folio' => [
                'required',
                'string',
                'max:10',
                Rule::unique('tblservicios_enc', 'folio')->ignore($id),
            ],
            'nombre' => 'required|string|max:255',
            'id_tiposervicio' => 'required|integer',
            'estado' => 'required|string',
            'id_vendedor' => 'required|integer',
            'id_cliente' => 'required|integer',
            'id_atencion' => 'required|integer',
            'fecha_inicio' => 'required|date',
            'fecha_limite' => 'required|date|after:fecha_inicio',
        ];
    }

    /**
     * Mensajes de validación personalizados
     */
    public static function messages()
    {
        return [
            'folio.unique' => 'El folio ya existe en el sistema. Por favor, ingrese un folio diferente.',
            'folio.required' => 'El folio es obligatorio.',
            'folio.max' => 'El folio no puede tener más de 10 caracteres.',
            'fecha_limite.after' => 'La fecha límite debe ser posterior a la fecha de inicio.',
        ];
    }
    
    public function detalles()
    {
        return $this->hasMany(ServiciosDet::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }

    public function personaAtencion()
    {
        return $this->belongsTo(ClientesAtencion::class, 'id_atencion');
    }
}
