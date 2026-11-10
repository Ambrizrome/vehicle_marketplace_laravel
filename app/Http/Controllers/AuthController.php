<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

// Login de la API con tokens de Sanctum
class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    // POST /api/register -> crea el usuario y devuelve su token
    public function register(RegisterRequest $request)
    {
        return response()->json($this->authService->registrar($request->validated()), 201);
    }

    // POST /api/login -> devuelve un token nuevo si las credenciales son correctas
    public function login(LoginRequest $request)
    {
        return response()->json($this->authService->login($request->validated()));
    }

    // POST /api/logout -> invalida el token usado en la petición
    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Sesión cerrada']);
    }

    // GET /api/user -> el usuario dueño del token
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
