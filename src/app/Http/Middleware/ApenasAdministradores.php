<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Libera a rota só para usuários administradores. */
class ApenasAdministradores
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_admin, 403, 'Acesso restrito a administradores.');

        return $next($request);
    }
}
