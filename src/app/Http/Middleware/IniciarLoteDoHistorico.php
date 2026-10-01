<?php

namespace App\Http\Middleware;

use App\Services\HistoricoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Cada requisição abre um novo lote do histórico: tudo que ela alterar sai agrupado como um único salvamento. */
class IniciarLoteDoHistorico
{
    public function __construct(private HistoricoService $historico) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->historico->novoLote();

        return $next($request);
    }
}
