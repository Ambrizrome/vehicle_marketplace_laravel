<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

// Segunda capa contra inyección SQL: la protección real es Eloquent (consultas preparadas con ?).
// Este middleware detecta patrones típicos de ataque, responde 422 y deja registro en el log.
class DetectarInyeccionSql
{
    // Las contraseñas se guardan hasheadas y nunca llegan como texto al SQL; pueden llevar ' o --
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    // Lista corta a propósito: bloquear cualquier comilla rechazaría nombres como O'Brien
    protected $patrones = [
        '/[\'"]\s*(or|and)\s+\S+\s*=/i',                             // ' OR 1=1  |  ' or 'a'='a
        '/\bunion\s+(all\s+)?select\b/i',                            // UNION SELECT (leer otras tablas)
        '/;\s*(drop|delete|update|insert|truncate|alter)\b/i',      // ; DROP TABLE (encadenar otra consulta)
        '/[\'"]\s*(--|\/\*)/',                                       // ' --  (comentar el resto de la consulta)
    ];

    public function handle(Request $request, Closure $next)
    {
        // Arr::dot aplana arreglos anidados: revisa body y query string, campo por campo
        foreach (Arr::dot($request->all()) as $campo => $valor) {
            if (!is_string($valor) || in_array($campo, $this->except)) {
                continue;
            }

            foreach ($this->patrones as $patron) {
                if (preg_match($patron, $valor)) {
                    Log::warning('Posible intento de inyección SQL', [
                        'ip'    => $request->ip(),
                        'ruta'  => $request->method() . ' ' . $request->path(),
                        'campo' => $campo,
                        'valor' => mb_substr($valor, 0, 100),
                    ]);

                    // ValidationException: Laravel la convierte en una respuesta 422 con el mensaje
                    throw ValidationException::withMessages([$campo => 'El campo contiene un patrón no permitido.']);
                }
            }
        }

        return $next($request);
    }
}
