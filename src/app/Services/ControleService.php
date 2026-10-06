<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Support\ColunasControle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Consultas da planilha de controle: uma linha por item de pedido, agrupada por ano do pedido.
 */
class ControleService
{
    /** Anos que têm pedidos (mais recente primeiro). */
    public function anos(): Collection
    {
        return Pedido::query()->whereHas('itens')->toBase()->get(['data_pedido', 'created_at'])
            ->map(fn ($p) => (int) substr((string) ($p->data_pedido ?? $p->created_at), 0, 4))
            ->unique()->sortDesc()->values();
    }

    /**
     * Itens de um ano, na ordem da planilha (data de entrega; sem data por último).
     *
     * @param  array{q?: string, status?: string, responsavel?: string, ocultar_entregues?: bool}  $filtros
     */
    public function itensDoAno(int $ano, array $filtros = []): Collection
    {
        return $this->consulta($ano, $filtros)->get();
    }

    /** Pedidos de um ano com os itens carregados (para a exportação resumida, uma linha por pedido). */
    public function pedidosDoAno(int $ano): Collection
    {
        return Pedido::query()->doAno($ano)->whereHas('itens')->with('itens')->orderBy('id')->get();
    }

    /** Responsáveis já usados (para o filtro). */
    public function responsaveis(): Collection
    {
        return PedidoItem::query()->whereNotNull('responsavel')->distinct()->orderBy('responsavel')->pluck('responsavel')
            ->map(fn ($r) => trim($r))->filter()->unique(fn ($r) => mb_strtolower($r))->values();
    }

    private function consulta(int $ano, array $filtros): Builder
    {
        $q = trim($filtros['q'] ?? '');

        return PedidoItem::query()->with('pedido')
            ->whereHas('pedido', fn ($pedido) => $pedido->doAno($ano))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('denominacao', 'like', "%$q%")
                ->orWhere('cidade_entrega', 'like', "%$q%")
                ->orWhereHas('pedido', fn ($p) => $p->where('numero', 'like', "%$q%")->orWhere('cliente', 'like', "%$q%"))))
            ->when(! empty($filtros['status']), fn ($query) => $query->where('status', $filtros['status']))
            ->when(! empty($filtros['responsavel']), fn ($query) => $query->whereRaw('lower(responsavel) = ?', [mb_strtolower($filtros['responsavel'])]))
            ->when(! empty($filtros['ocultar_entregues']), fn ($query) => $query->whereNotIn('status', ColunasControle::STATUS_ENCERRADOS))
            ->orderByRaw('dt_entrega is null')->orderBy('dt_entrega')->orderBy('id');
    }
}
