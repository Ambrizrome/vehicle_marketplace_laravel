<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

// Contra XSS en la entrada: si un campo trae etiquetas HTML (<script>, <img ...>), responde 422.
// Se rechaza en vez de limpiar: strip_tags("Juan <3 Ana") deja "Juan " y se perderían datos sin avisar.
class PrevenirXss
{
    // Las contraseñas se guardan hasheadas y nunca se muestran; pueden llevar < o >
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function handle(Request $request, Closure $next)
    {
        // Arr::dot aplana arreglos anidados: revisa body y query string, campo por campo
        foreach (Arr::dot($request->all()) as $campo => $valor) {
            if (!is_string($valor) || in_array($campo, $this->except)) {
                continue;
            }

            // Si quitarle las etiquetas cambia el texto, es que traía HTML
            if ($valor !== strip_tags($valor)) {
                throw ValidationException::withMessages([$campo => 'No se permite HTML en este campo.']);
            }
        }

        return $next($request);
    }
}
