<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Valida los datos de POST /api/pedidos antes de llegar al controller (si falla, responde 422)
class StorePedidoRequest extends FormRequest
{
    // Cualquiera puede crear pedidos (la API aún no tiene login)
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'modelo_id'      => 'required|exists:modelos,id',
            'sucursal_id'    => 'required|exists:sucursales,id',
            'cliente_nombre' => 'required|string|max:100',
            'cliente_email'  => 'required|email',
        ];
    }
}
