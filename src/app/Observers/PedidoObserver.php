<?php

namespace App\Observers;

use App\Models\Pedido;
use App\Services\HistoricoService;

/** Registra no histórico as alterações e a exclusão de pedidos (a criação é registrada pelos serviços). */
class PedidoObserver
{
    public function __construct(private HistoricoService $historico) {}

    public function updated(Pedido $pedido): void
    {
        $this->historico->pedidoAlterado($pedido);
    }

    public function deleted(Pedido $pedido): void
    {
        $this->historico->pedidoExcluido($pedido);
    }
}
