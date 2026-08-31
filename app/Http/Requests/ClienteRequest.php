<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClienteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'rfc'=>['unique:tblclientes_vales'],
            'curp'=>['unique:tblclientes_vales']
        ];
    }

    public function messages()
    {
        return[
        'rfc.unique' => 'Lo sentimos, el RFC insertado ya esta registrada',
        'curp.unique' => 'Lo sentimos, la CURP insertada ya esta registrada'
        ];
    }
}
