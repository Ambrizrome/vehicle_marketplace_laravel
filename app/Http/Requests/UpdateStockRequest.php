<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Valida los datos para actualizar el stock de un modelo
class UpdateStockRequest extends FormRequest
{
    // Que sea admin ya se revisa en la ruta (can:admin)
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'stock' => 'required|integer|min:0',
        ];
    }
}