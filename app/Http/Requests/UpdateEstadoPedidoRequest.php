<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Valida los datos de PATCH /api/pedidos/{id}/estado (si falla, responde 422)
class UpdateEstadoPedidoRequest extends FormRequest
{
    // Que sea admin ya se revisa en la ruta (can:admin)
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Un pedido solo puede pasar de pendiente a confirmado (venta) o cancelado
            'estado' => 'required|in:confirmado,cancelado',
        ];
    }
}
