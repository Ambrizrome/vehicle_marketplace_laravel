<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Valida los datos de POST /api/login (si falla, responde 422)
class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email'    => 'required|email',
            'password' => 'required|string',
        ];
    }
}
