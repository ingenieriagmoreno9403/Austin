<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValeraRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'folio_inicio'=>['unique:tblvaleras'],
            'folio_fin'=>['unique:tblvaleras']
        ];
    }

    public function messages()
    {
        return[
        'folio_inicio.unique' => 'Lo sentimos, ya existe',
        'folio_fin.unique' => 'Lo sentimos, ya existe'
        ];
    }
}
