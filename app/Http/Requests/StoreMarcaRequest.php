<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarcaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('marcas', 'nombre')->ignore($this->route('id'))
            ],
            'pais_origen' => 'required|string|max:100',
        ];
    }
}