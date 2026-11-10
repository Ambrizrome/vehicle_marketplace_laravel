<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    // Crea el usuario y le emite su primer token
    public function registrar(array $datos): array
    {
        $user = User::create([
            'name'     => $datos['name'],
            'email'    => $datos['email'],
            'password' => Hash::make($datos['password']),
        ]);

        return $this->respuestaConToken($user);
    }

    // Revisa email y contraseña; si coinciden emite un token nuevo, si no lanza un 422
    public function login(array $datos): array
    {
        $user = User::where('email', $datos['email'])->first();

        // Mismo mensaje si el email no existe o la contraseña es incorrecta, para no revelar qué emails están registrados
        if (!$user || !Hash::check($datos['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Credenciales incorrectas']);
        }

        return $this->respuestaConToken($user);
    }

    // Borra solo el token con el que se hizo la petición (las otras sesiones siguen activas)
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    // plainTextToken solo se puede leer aquí; en la BD se guarda hasheado
    private function respuestaConToken(User $user): array
    {
        return [
            'user'  => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }
}
