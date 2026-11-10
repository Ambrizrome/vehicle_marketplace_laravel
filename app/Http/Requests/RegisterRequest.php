<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Valida los datos de POST /api/register (si falla, responde 422)
class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ];
    }
}
