<?php

namespace App\Observers;

use App\Models\PedidoItem;
use App\Services\HistoricoService;

/** Registra no histórico a criação, as alterações e a exclusão de itens de pedido. */
class PedidoItemObserver
{
    public function __construct(private HistoricoService $historico) {}

    public function created(PedidoItem $item): void
    {
        $this->historico->itemCriado($item);
    }

    public function updated(PedidoItem $item): void
    {
        $this->historico->itemAlterado($item);
    }

    public function deleted(PedidoItem $item): void
    {
        $this->historico->itemExcluido($item);
    }
}
