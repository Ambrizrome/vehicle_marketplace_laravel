<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Contra XSS en la salida: escapa el HTML dentro del JSON y agrega cabeceras de seguridad a todas las respuestas.
// Defensa en profundidad: protege aunque un dato con HTML haya entrado por otro camino (seeder, la base directo).
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // < > & ' " salen como su código unicode (< como u003C): el cliente al leer el JSON recibe el texto original,
        // pero un navegador nunca los interpreta como HTML
        if ($response instanceof JsonResponse) {
            $response->setEncodingOptions(
                $response->getEncodingOptions() | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            );
        }

        // nosniff: el navegador no puede "adivinar" que el JSON es HTML y ejecutarlo
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // La API no sirve páginas: no se permite cargar scripts ni meterla en un iframe
        $response->headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
