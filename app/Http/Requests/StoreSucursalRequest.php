<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Valida los datos para crear y actualizar una sucursal
class StoreSucursalRequest extends FormRequest
{
    public function authorize()
    {
        // El permiso de administrador se valida desde las rutas
        return true;
    }

    public function rules()
    {
        return [
            'nombre'    => 'required|string|max:100',
            'ciudad'    => 'required|string|max:100',
            'direccion' => 'required|string|max:255',
            'telefono'  => 'nullable|string|max:20',
        ];
    }
}