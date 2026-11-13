<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuscarModelosRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'marca_id'   => 'nullable|integer',
            'anio'       => 'nullable|integer',
            'precio_min' => 'nullable|numeric',
            'precio_max' => 'nullable|numeric',
            'tipo'       => 'nullable|string|max:100',
            'q'          => 'nullable|string|max:100',
        ];
    }
}