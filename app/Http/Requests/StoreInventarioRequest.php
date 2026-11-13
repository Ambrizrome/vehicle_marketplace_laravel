<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Valida los datos para agregar un modelo al inventario de una sucursal
class StoreInventarioRequest extends FormRequest
{
    // Que sea admin ya se revisa en la ruta (can:admin)
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'modelo_id' => 'required|exists:modelos,id',
            'stock' => 'required|integer|min:0',
        ];
    }
}