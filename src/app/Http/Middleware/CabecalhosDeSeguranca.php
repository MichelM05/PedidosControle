<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança em todas as respostas: sem clickjacking, sem adivinhação de tipo de arquivo,
 * sem vazar a URL completa, HTTPS forçado (quando a requisição é segura) e Content-Security-Policy nas páginas HTML.
 */
class CabecalhosDeSeguranca
{
    public function handle(Request $request, Closure $next): Response
    {
        $resposta = $next($request);

        $resposta->headers->set('X-Frame-Options', 'DENY');
        $resposta->headers->set('X-Content-Type-Options', 'nosniff');
        $resposta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $resposta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $resposta->headers->remove('X-Powered-By');

        if ($request->isSecure()) {
            $resposta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP só nas páginas HTML (não no PDF original) e fora do modo de desenvolvimento do Vite (npm run dev)
        $html = str_starts_with((string) $resposta->headers->get('Content-Type'), 'text/html');
        if ($html && ! file_exists(public_path('hot'))) {
            $resposta->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self'",
                "style-src 'self' 'unsafe-inline'", // o React usa style="" em cores e larguras
                "img-src 'self' data:",
                "font-src 'self' data:",
                "connect-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ]));
        }

        return $resposta;
    }
}
