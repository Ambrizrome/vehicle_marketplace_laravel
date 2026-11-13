<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreModeloRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'marca_id' => 'required|exists:marcas,id',
            'nombre'   => 'required|string|max:100',
            'anio'     => 'required|integer|between:1900,2100',
            'tipo'     => 'required|string|max:50',
            'precio'   => 'required|numeric|min:0',
        ];
    }
}